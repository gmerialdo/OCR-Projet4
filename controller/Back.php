<?php

require_once "controller/Page.php";
require_once "conf.php";

class Back extends Page
{

    public function __construct($url){
        $url = array_slice($url, 1);
        parent::__construct($url);
        $this->_defaultPage = "admin";
    }

    //adds a complement before using parent::getPage() to securize all the admin interface: only connect if logged!
    public function getPage(){
        //check if input and not logged
        if (!empty($_POST) && (Session::get("username")==null)){
            $logged = $this->checkLogin(
                filter_input(INPUT_POST, "user", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH),
                filter_input(INPUT_POST, "pw", FILTER_SANITIZE_SPECIAL_CHARS,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH)
            );
            //if correct username and password then login
            if ($logged){
                Session::put("username", filter_input(INPUT_POST, "user", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
            }
            else{
                echo "<p style=\"text-align:center;\">L'identification a échoué ; veuillez entrer à nouveau votre identifiant et votre mot de passe.</p>";
            }
        }
        if (Session::get("username")==null){
            return $this->loginPage();
        }
        //else the user is logged in so go to the page in admin interface
        else {
            return parent::getPage();
        }
    }

    //NB: The functions fct_to_call() for Back must return an array with keys: {{ path }}, {{ pageTitle }} and {{ content }} and as values the values to replace them in template back_template

    //leads to dashboard if logged or login page if not
    public function admin(){
        //display the dashboard admin page
        require_once "controller/Post.php";
        $post = new Post();
        require_once "controller/Comment.php";
        $comment = new Comment();
        //get the featured_post info & nb of comments
        $feat_post = $post->getFeaturedPost();
        $nb_to_mod = $comment->countCommentsV(2);
        //according to number of comments to moderate:
        switch ($nb_to_mod["nb"]){
            case 0:
                $toMod = "<p>Aucun commentaire à modérer</p>";
                break;
            case 1:
                $comments_to_mod = $comment->getCommentsV(2);
                $comments_to_mod["{{ path }}"] = $GLOBALS["path"];
                $toMod = View::makeHtml($comments_to_mod, "commentsToMod_template");
                break;
            default:
                $comments_to_mod = $comment->getCommentsV(2);
                for ($i=0; $i<$nb_to_mod["nb"]; $i++){
                    $comments_to_mod[$i]["{{ path }}"] = $GLOBALS["path"];
                }
                $toMod = View::makeLoopHtml($comments_to_mod, "commentsToMod_template");
                break;
        }
        //$html: dashboard content ( {{ content_admin_ backadmin_template)
        $html = View::makeHtml(
            [
                "{{ path }}" => $GLOBALS["path"],
                "{{ feat_post_id }}" => $feat_post["id"],
                "{{ feat_post_nb }}" => $feat_post["nb_chapter"],
                "{{ feat_post_title }}" => $feat_post["title"],
                "{{ feat_post_content }}" => $feat_post["content"],
                "{{ feat_post_date }}" => $feat_post["feat_date"],
                "{{ content_commentsToMod }}" => $toMod
            ], "backadmin_dashboard_template");
        //$html2: add the backadmin content
        $html2 = View::addBackTpl($html);
        return [
            "{{ pageTitle }}" => "Tableau de bord",
            "{{ content }}" => $html2,
            "{{ path }}" => $GLOBALS["path"]
        ];
    }

    //login page
    public function loginPage(){
        $html = View::makeHtml(
                [
                    "{{ path }}" => $GLOBALS["path"]
                ], "login_template");
        return [
            "{{ pageTitle }}" => "login",
            "{{ content }}" => $html,
            "{{ path }}" => $GLOBALS["path"]
        ];
    }

    //check entered login and password
    public function checkLogin($user, $pw){
        $hash = hash("sha256", $pw);
        $req = [
            "fields" => [
                "*"
            ],
            "from" => "users",
            "where" => [
                "username ='$user'",
                "password ='$hash'"
                ]
        ];
        $data = Model::select($req);
        //return true if not empty or false otherwise
        return (!empty($data["data"]));
    }

    public function logout(){
        session_destroy();
        return $this->loginPage();
    }

    //add a new chapter
    public function addChapterPage(){
        require_once "controller/Post.php";
        $post = new Post();
        //$html: replace dashboard content ( {{ content_admin_page }} in backadmin_template)
        if (isset($_POST["title"]) && isset($_POST["content"])) {
            $title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
            $title = ucfirst($title);
            $content = filter_input(INPUT_POST, "content", FILTER_SANITIZE_SPECIAL_CHARS);
            $post->cancelFeatured();
            //count how many chapters there are to add the good number
            $nb = $post->countPosts();
            $chapter_nb = $nb["nb"] + 1;
            $data = [
                $chapter_nb,
                date('Y-m-d'),
                $title,
                htmlspecialchars_decode($content),
                1
            ];
            $post_added = $post->addPost($data);
            if ($post_added){
                $html = View::makeHtml([
                    "{{ path }}" => $GLOBALS["path"]
                        ], "add_post_message");
            }
            else{
                $html = View::errorDisplayBack();
            }
        }
        else {
            $html = View::errorDisplayBack();
        }
        //$html: replace dashboard content ( {{ content_admin_page }} in backadmin_template)
        //$html2: add the backadmin content
        $html2 = View::addBackTpl($html);
        return [
            "{{ pageTitle }}" => "Tableau de bord",
            "{{ content }}" => $html2,
            "{{ path }}" => $GLOBALS["path"]
        ];
    }

    //modify a chapter
    public function modifyChapter(){
        require_once "controller/Post.php";
        $post = new Post();
        $id = $this->_url[1];
        //$html: replace dashboard content ( {{ content_admin_page }} in backadmin_template)
        //if post data then apply and update post in db
        if (isset($_POST["new_title"]) OR isset($_POST["new_content"])){
            $data = [];
            $fields = [];
            if (isset($_POST["new_title"])){
                $new_title = filter_input(INPUT_POST, "new_title", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
                $new_title = ucfirst($new_title);
                $data[] = $new_title;
                $fields[] = "title";
            }
            if (isset($_POST["new_content"])){
                $new_content = filter_input(INPUT_POST, "new_content", FILTER_SANITIZE_SPECIAL_CHARS);
                $data[] = htmlspecialchars_decode($new_content);
                $fields[] = "content";
            }
            $post_updated = $post->updatePost($fields, $data, $id);
            if ($post_updated){
                $html = View::makeHtml([
                    "{{ path }}" => $GLOBALS["path"]
                        ], "update_post_message");
            }
            else{
                $html = View::errorDisplayBack();
            }
        }
        //if no post data then display page with TinyMCE to modify chapter
        else {
            $content = $post->getPost($id);
            $content["{{ path }}"] = $GLOBALS["path"];
            $html = View::makeHtml($content, "backadmin_modifyChapter_template");
        }
        //$html2: add the backadmin content
        $html2 = View::addBackTpl($html);
        return [
            "{{ pageTitle }}" => "Modifier le chapitre",
            "{{ content }}" => $html2,
            "{{ path }}" => $GLOBALS["path"]
        ];
    }

    //ask for confirmation to delete chapter
    public function deleteChapter(){
        $id = $this->_url[1];
        //$html: replace dashboard content ( {{ content_admin_page }} in backadmin_template)
        $html = View::makeHtml(["{{ path }}" => $GLOBALS["path"], "{{ post_id }}" => $id], "deleteconfirmation_post_message");
        //$html2: add the backadmin content
        $html2 = View::addBackTpl($html);
        return [
            "{{ pageTitle }}" => "Confirmer la suppression",
            "{{ content }}" => $html2,
            "{{ path }}" => $GLOBALS["path"]
        ];
    }

    //delete chapter for sure
    public function deleteChapterConfirmed(){
        require_once "controller/Post.php";
        $post = new Post();
        $id = $this->_url[1];
        $nb = $post->countPosts();
        //get the chapter number
        $get_nb_chapter = $post->getNbChapter($id);
        $nb_chap = $get_nb_chapter["nb_chapter"];
        //$html: replace dashboard content ( {{ content_admin_page }} in backadmin_template)
        $post_deleted = $post->deletePost($id);
        if ($post_deleted){
            //if last chapter and at least one chapter left then set previous one as featured post
            if (($nb["nb"] == $nb_chap) && ($nb["nb"] > 1)){
                $post->setFeatured($nb_chap-1);
            }
            //else (if not last chapter) change id of all chapters after that one
            else {
                for ($i=$nb_chap; $i<$nb["nb"]; $i++){
                    $post->setPostNb($i+1, $i);
                }
            }
            //$html: replace dashboard content ( {{ content_admin_page }} in backadmin_template)
            $html = View::makeHtml(["{{ path }}" => $GLOBALS["path"]], "delete_post_message");
        }
        else{
            $html = View::errorDisplayBack();
        }
        //$html2: add the backadmin content
        $html2 = View::addBackTpl($html);
        return [
            "{{ pageTitle }}" => "Chapitre supprimé",
            "{{ content }}" => $html2,
            "{{ path }}" => $GLOBALS["path"]
        ];
    }

    //see all chapters and manage
    public function allChaptersPage(){
        require_once "controller/Post.php";
        $post = new Post();
        $nb = $post->countPosts();
        //according to number of posts - add {{ path }}
        switch ($nb["nb"]){
            case 0:
                $posts_content = "<p>Aucun chapitre publié</p>";
                break;
            case 1:
                $content = $post->getAllPosts();
                $content["{{ path }}"] = $GLOBALS["path"];
                $posts_content = View::makeHtml($content , "backadmin_chapterline_template");
                break;
            default:
                $content = $post->getAllPosts();
                for ($i=0; $i<$nb["nb"]; $i++){
                    $content[$i]["{{ path }}"] = $GLOBALS["path"];
                }
                $posts_content = View::makeLoopHtml($content, "backadmin_chapterline_template");
                break;
        }
        //$html: replace dashboard content ( {{ content_admin_page }} in backadmin_template)
        $html = View::makeHtml(
                [
                    "{{ content_allChapters }}" => $posts_content
                ], "backadmin_allchapters_template");
        //$html2: add the backadmin content
        $html2 = View::addBackTpl($html);
        return [
            "{{ pageTitle }}" => "Tableau de bord",
            "{{ content }}" => $html2,
            "{{ path }}" => $GLOBALS["path"]
        ];
    }

    //see all comments and manage
    public function allCommentsPage(){
        require_once "controller/Comment.php";
        $comment = new Comment();
        //get comments to moderate
        $nb_to_mod = $comment->countCommentsV(2);
        //according to number of comments to moderate:
        switch ($nb_to_mod["nb"]){
            case 0:
                $toMod = "<p>Aucun commentaire à modérer</p>";
                break;
            case 1:
                $comments_to_mod = $comment->getCommentsV(2);
                $comments_to_mod["{{ path }}"] = $GLOBALS["path"];
                $toMod = View::makeHtml($comments_to_mod, "commentsToMod_template");
                break;
            default:
                $comments_to_mod = $comment->getCommentsV(2);
                for ($i=0; $i<$nb_to_mod["nb"]; $i++){
                    $comments_to_mod[$i]["{{ path }}"] = $GLOBALS["path"];
                }
                $toMod = View::makeLoopHtml($comments_to_mod, "commentsToMod_template");
                break;
        }
        //get comments currently valid
        $nb_valid = $comment->countCommentsV(1);
        //according to number of valid comments
        switch ($nb_valid["nb"]){
            case 0:
                $valid = "<p>Aucun commentaire validé</p>";
                break;
            case 1:
                $valid_comments = $comment->getCommentsV(1);
                //get the chapter nb!
                $valid_comments["{{ path }}"] = $GLOBALS["path"];
                $valid = View::makeHtml($valid_comments, "validComments_template");
                break;
            default:
                $valid_comments = $comment->getCommentsV(1);
                for ($i=0; $i<$nb_valid["nb"]; $i++){
                    //get the chapter nb!
                    $valid_comments[$i]["{{ path }}"] = $GLOBALS["path"];
                }
                $valid = View::makeLoopHtml($valid_comments, "validComments_template");
                break;
        }
        //get comments deleted
        $nb_deleted = $comment->countCommentsV(0);
        //according to number of deleted comments
        switch ($nb_deleted["nb"]){
            case 0:
                $deleted = "<p>Aucun commentaire refusé</p>";
                break;
            case 1:
                $del_comments = $comment->getCommentsV(0);
                $del_comments["{{ path }}"] = $GLOBALS["path"];
                $deleted = View::makeHtml($del_comments, "deletedComments_template");
                break;
            default:
                $del_comments = $comment->getCommentsV(0);
                for ($i=0; $i<$nb_deleted["nb"]; $i++){
                    $del_comments[$i]["{{ path }}"] = $GLOBALS["path"];
                }
                $deleted = View::makeLoopHtml($del_comments, "deletedComments_template");
                break;
        }
        //$html: replace dashboard content ( {{ content_admin_ backadmin_template)
        $html = View::makeHtml(
            [
                "{{ path }}" => $GLOBALS["path"],
                "{{ content_toMod }}" => $toMod,
                "{{ content_valid }}" => $valid,
                "{{ content_deleted }}" => $deleted
            ], "backadmin_allcomments_template");
        //$html2: add the backadmin content
        $html2 = View::addBackTpl($html);
        return [
            "{{ pageTitle }}" => "Tableau de bord",
            "{{ content }}" => $html2,
            "{{ path }}" => $GLOBALS["path"]
        ];
    }

    //accept a comment at moderation (updates 'valid' at 1)
    public function acceptComment(){
        require_once "controller/Comment.php";
        $comment = new Comment();
        $id = $this->_url[1];
        $accept = $comment->acceptComment($id);
        if ($accept){
            header('Location: '.$GLOBALS["path"].'/admin/allCommentsPage');
            exit();
        }
        else {
            $html = View::errorDisplayBack();
            $html2 = View::addBackTpl($html);
            return [
                "{{ pageTitle }}" => "Tableau de bord",
                "{{ content }}" => $html2,
                "{{ path }}" => $GLOBALS["path"]
            ];
        }
    }

    //refuse a comment at moderation (updates 'valid' at 0)
    public function refuseComment(){
        require_once "controller/Comment.php";
        $comment = new Comment();
        $id = $this->_url[1];
        $comment->refuseComment($id);
        header('Location: '.$GLOBALS["path"].'/admin/allCommentsPage');
    }

}


