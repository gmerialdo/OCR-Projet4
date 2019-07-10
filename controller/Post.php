<?php

class Post
{

    //get a post by id
    public function getPost($id){
        $req = [
            "fields" => [
                'id AS "{{ post_id }}"',
                'nb_chapter AS "{{ post_nb }}"',
                'title AS "{{ post_title }}"',
                'content AS "{{ post_content }}"',
                'DATE_FORMAT(date_published, \'%d/%m/%Y\') AS "{{ post_date }}"',
                'featured'
            ],
            "from" => "posts",
            "where" => ["id = ".$id],
            "limit" => 1
        ];
        $data = Model::select($req);
        return $data["data"];
    }

    //get the featured post
    public function getFeaturedPost(){
        $req = [
            "fields" => [
                'id',
                'nb_chapter',
                'title',
                'content',
                'DATE_FORMAT(date_published, \'%d/%m/%Y\') AS "feat_date"'
            ],
            "from" => "posts",
            "where" => ["featured = 1"],
            "limit" => 1
        ];
        $data = Model::select($req);
        return $data["data"];
    }

    //get all posts
    public function getAllPosts(){
        $req = [
            "fields"  => [
                'id AS "{{ post_id }}"',
                'nb_chapter AS "{{ post_nb }}"',
                'title AS "{{ post_title }}"',
                'DATE_FORMAT(date_published, \'%d/%m/%Y\') AS "{{ post_date }}"',
                'SUBSTRING(content,1,200) AS "{{ post_content }}"'
            ],
            "from"  => "posts",
            "where" => [ "date_published IS NOT NULL" ]
        ];
        $data = Model::select($req);
        return $data["data"];
    }

    //get the 5 last posts
    public function getLastPosts(){
        $req = [
            "fields" => [
                'id',
                'nb_chapter',
                'title',
                'SUBSTRING(content,1,300) AS "post_content"',
                'DATE_FORMAT(date_published, \'%d/%m/%Y\') AS "post_date"'
            ],
            "from" => "posts",
            "order" => "id DESC",
            "where" => ["featured = 0"],
            "limit" => 5
        ];
        $data = Model::select($req);
        return $data["data"];
    }

    //count all posts
    public function countPosts(){
        $req = [
            "fields" => [
                'COUNT( *) AS "nb"'
            ],
            "from" => "posts",
            "where" => [ "date_published IS NOT NULL" ]
        ];
        $data = Model::select($req);
        return $data["data"];
    }

    //set featured post
    public function setFeatured($nb){
        //returns true if feat post has been "featured"
        $req = [
            "table"  => "posts",
            "fields" => [
                'featured'
            ],
            "where" => ["nb_chapter = ".$nb]
        ];
        return Model::update($req, [1]);
    }

    //cancel a featured post
    public function cancelFeatured(){
        //returns true if feat post has been "unfeatured"
        $req = [
            "table"  => "posts",
            "fields" => ['featured'],
            "where" => ["featured = 1"]
        ];
        return Model::update($req, [0]);
    }

    //get the nb_chapter with id
    public function getNbChapter($id){
        $req = [
            "fields" => [
                'nb_chapter'
            ],
            "from" => "posts",
            "where" => [ "id = ".$id ]
        ];
        $data = Model::select($req);
        return $data["data"];
    }

    //add a post
    public function addPost($data){
        $req = [
            "table"  => "posts",
            "fields" => [
                'nb_chapter',
                'date_published',
                'title',
                'content',
                'featured'
            ]
        ];
        return Model::insert($req, $data);
    }

    //update a post
    public function updatePost($fields, $data, $id){
        $req = [
            "table"  => "posts",
            "fields" => $fields,
            "where" => ["id = ".$id]
        ];
        return Model::update($req, $data);
    }

    //delete a post
    public function deletePost($id){
        $req = [
            "from" => "posts",
            "where" => ["id = ".$id],
        ];
        return Model::delete($req);
    }

    public function setPostNb($old_nb, $new_nb){
        $req = [
            "table"  => "posts",
            "fields" => ['nb_chapter'],
            "where" => ["nb_chapter = ".$old_nb],
            "limit" => 1
        ];
        return Model::update($req, [$new_nb]);
    }

}
