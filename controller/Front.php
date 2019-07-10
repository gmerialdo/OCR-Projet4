<?php

require_once "controller/Page.php";
require_once "conf.php";

class Front extends Page
{

    public function __construct($url){
        parent::__construct($url);
        $this->_defaultPage = "homePage";
    }

    //NB: The functions fct_to_call() for Front must return an array with keys: {{ path }}, {{ pageTitle }} and {{ content }} and as values the values to replace them in template front_template

    //get home page
    public function homePage(){
        $post = new Post();
        require_once "controller/Comment.php";
        $comment = new Comment();
        //get the featured_post info & nb of comments
        $content = $post->getFeaturedPost();
        $nb_comments_feat = $comment->countComments($content["id"]);
        $html = View::makeHtml(
            [
                "{{ feat_post_nb }}" => $content["nb_chapter"],
                "{{ feat_post_id }}" => $content["id"],
                "{{ feat_post_title }}" => $content["title"],
                "{{ feat_post_content }}" => $content["content"],
                "{{ feat_post_date }}" => $content["feat_date"],
                "{{ feat_nb_comments }} " => $nb_comments_feat["nb_comments"]
            ], "featured_post_template");

        //adds the html for max 5 next posts
        $content2 = $post->getLastPosts();
        for ($i=0; $i<sizeof($content2); $i++){
            $nb_comments = $comment->countComments($content2["".$i]["id"]);
            $html .= View::makeHtml(
                [
                    "{{ post_id }}" => $content2["".$i]["id"],
                    "{{ post_nb }}" => $content2["".$i]["nb_chapter"],
                    "{{ post_title }}" => $content2["".$i]["title"],
                    "{{ post_content }}" => $content2["".$i]["post_content"],
                    "{{ post_date }}" => $content2["".$i]["post_date"],
                    "{{ post_nb_comments }} " => $nb_comments["nb_comments"]
            ], "last_posts_template");
        }
        //adds a link to all posts page
        $html .= "<a class=\"link_all_posts\" href=\"allPostsPage\">Voir tous les chapitres</a>";
        return [
            "{{ pageTitle }}" => "Accueil",
            "{{ content }}" => $html,
            "{{ path }}" => $GLOBALS["path"]
        ];
    }

    //get a page with one given post
    public function postPage(){
        require_once "controller/Post.php";
        $post = new Post();
        require_once "controller/Comment.php";
        $comment = new Comment();
        //gets the post id in URL
        $idPost = $this->_url[1];
        //adds post info
        $html_post = $post->getPost($idPost);
        $html = View::makeHtml($html_post, "post_template");
        $html .= "<br/><div id=\"comments\"><h2>Commentaires</h2><ul>";
        //count nb of comments for the post
        $nb_comments = $comment->countComments($idPost);
        //according to nb of comments
        switch ($nb_comments["nb_comments"]){
            case 0:
                $html .= "<p>Pas encore de commentaire</p>";
                break;
            case 1:
                //adds comments infos
                $html_comments = $comment->getComments($idPost);
                $html .= View::makeHtml($html_comments, "comments_template");
                break;
            default:
                $html_comments = $comment->getComments($idPost);
                $html .= View::makeLoopHtml($html_comments, "comments_template");
                break;
        }
        //adds the "leave a comment" form
        $html .= "</ul>";
        $html .= View::makeHtml([
                    "{{ path }}" => $GLOBALS["path"],
                    "{{ idPost }}" => $idPost
                ],"add_comment_template");
        $html .= "</div>";
        return [
            "{{ pageTitle }}" => $html_post["{{ post_title }}"],
            "{{ content }}" => $html,
            "{{ path }}" => $GLOBALS["path"]
        ];
    }

    //get the page with all posts listed
    public function allPostsPage(){
        require_once "controller/Post.php";
        $post = new Post();
        $content = $post->getAllPosts();
        $html = "<div class=\"page_all_chapters\"><h1>Tous les chapitres:</h1>";
        $html .= View::makeLoopHtml($content, "all_posts_template");
        $html .= "</div>";
        return [
            "{{ pageTitle }}" => "Tous les chapitres",
            "{{ content }}" => $html,
            "{{ path }}" => $GLOBALS["path"]
        ] ;
    }

    //signal a comment
    public function signalCommentPage(){
        require_once "controller/Comment.php";
        $comment = new Comment();
        $idComment = $this->_url[1];
        $comment_signaled = $comment->signalComment($idComment);
        if ($comment_signaled) {
            $html = View::giveHtml("signal_message");
        }
        else $html = View::giveHtml("signal_message_error");
        return [
            "{{ pageTitle }}" => "Signalement commentaire",
            "{{ content }}" => $html,
            "{{ path }}" => $GLOBALS["path"]
        ] ;
    }

    //add a comment
    public function addCommentPage(){
        require_once "controller/Comment.php";
        $comment = new Comment();
        $idPost = $this->_url[1];
        if (isset($_POST["author"]) && isset($_POST["comment"])) {
            $author = filter_input(INPUT_POST, "author", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
            $author_comment = filter_input(INPUT_POST, "comment", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
            $data = [
                $author,
                $author_comment,
                date('Y-m-d'),
                $idPost,
                2
            ];
            $comment_added = $comment->addComment($data);
            if ($comment_added) {
                $html = View::makeHtml([
                    "{{ idPost }}" => $idPost,
                    "{{ path }}" => $GLOBALS["path"]
                        ],"add_comment_message");
            }
            else{
                $html = View::makeHtml([
                    "{{ path }}" => $GLOBALS["path"]
                        ], "add_comment_message_error");
            }
        }
        else {
            $html = View::makeHtml([
                    "{{ path }}" => $GLOBALS["path"]
                        ], "add_comment_message_error");
        }
        return [
            "{{ pageTitle }}" => "Ajout commentaire",
            "{{ content }}" => $html,
            "{{ path }}" => $GLOBALS["path"]
        ] ;
    }

}

