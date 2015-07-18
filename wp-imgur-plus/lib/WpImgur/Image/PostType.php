<?php

namespace WpImgur\Image;

class PostType {

  public $didRegister = false;
  public $sizes;

  function PostType() {
    $this->sizes = array(
      "s" => 90,
      "t" => 160,
      "m" => 320, 
      "l" => 640,
      "h" => 1024
    );
  }
  function register() {
    if (!$this->didRegister) {
      register_post_type(
        $this->getName(), $this->getOptions()
      );

      $this->didRegister = true;
    }
  }

  function create($postName, $content) {
    $post = array(
      'post_type'    => $this->getName(),
      'post_name'    => $this->toSlug($postName),
      'post_content' => $this->toJSON($content),
      'post_status'  => 'publish'
    );

    return wp_insert_post($post, true);
  }

  function update($id, $content) {
    global $wpdb;
    $JSON = $this->toJSON($content);
    $array = json_decode($JSON);
    $url = $array->original;
    $post = array(
      'ID'           => $id,
      'post_content' => $url
    );
    $url = str_replace("http://i.imgur.com/", "", $url);
    update_post_meta($id, "_wp_attached_file", $url);
    $mylink = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID = $id");
    $post_parent = $mylink->post_parent;
    $meta = get_post_meta( $id, "_wp_attachment_metadata", true );
    $a = maybe_unserialize($meta);
    // Update BD Post
    $uploads = wp_upload_dir();
    $baseimg = $this->strrstr($a['file'], "/", true);
    $baseimg = $baseimg."/";
    $wpdb->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content,'{$a['file']}','{$url}') where id = $post_parent");
    $wpdb->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content,'{$baseimg}{$a['sizes']['thumbnail']['file']}','".$this->getImage($url,'b')."') where id = $post_parent");
    $wpdb->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content,'{$baseimg}{$a['sizes']['medium']['file']}','".$this->getImage($url,'m')."') where id = $post_parent");
    $wpdb->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content,'{$baseimg}{$a['sizes']['large']['file']}','".$this->getImage($url,'l')."') where id = $post_parent");
    $wpdb->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content,'{$uploads['baseurl']}','http://i.imgur.com') where id = $post_parent");
    // Update BD Meta Post
    $this->detectSizesForReplace($a, $url);

    update_post_meta($id, "_wp_attachment_metadata", $a);
    return wp_update_post($post, true);
  }
  function updateArray($search, $replace, &$arrays) {
    foreach ($arrays as &$array) {
      if (is_array($array)) {
        $this->updateArray($search, $replace, $array);
      } else {
        if ($array==$search) {
          $array = $replace;
        }
      }
    }
  }
  function detectSize($tam) {
    foreach ($this->sizes as $key => $size) {
      if ($size>=$tam) {
        return $key;
      }
    }
  }
  function detectSizesForReplace(&$arrays, $url) {
    $arrays["file"] = $url;
    foreach ($arrays["sizes"] as $key => &$array) {
      $prom = ($array["width"]+$array["height"])/2;
      $size = $this->detectSize($prom);
      $this->updateArray($array["file"], $this->getImage($url,$size), $array);
    }
  }
  function strrstr($h, $n, $before = false) {
    $rpos = strrpos($h, $n);
    if($rpos === false) return false;
    if($before == false) return substr($h, $rpos);
    else return substr($h, 0, $rpos);
  }
  function getImage($url,$tam) {
    if(strlen($url)>0) {
      $res = explode(".", $url);
      if(isset($res[count($res)-2])) {
        $res[count($res)-2] = $res[count($res)-2].$tam;
        $res = implode(".", $res);
        return $res;
      } 
    }
  }
  function find($postName) {
    $options = array(
      'post_type'      => $this->getName(),
      'name'           => $this->toSlug($postName),
      'paged'          => 1,
      'posts_per_page' => 1
    );

    $query = new \WP_Query($options);
    $posts = $query->get_posts();

    if (count($posts) === 1) {
      $post    = $posts[0];
      $content = $post->post_content;
      $images  = $this->toImages($content);

      return array(
        'post' => $post,
        'images' => $images
      );
    } else {
      return false;
    }
  }

  function findBy($postNames, $pageNum = 1, $pageSize = 25) {
    global $wpdb;

    $inClause = $this->inClauseFor('post_name', $postNames);
    $postType = $this->getName();
    $sql = <<<SQL
    SELECT post_name, post_content
    From $wpdb->posts
    WHERE
      post_type = '$postType'
SQL;

    if ($inClause !== '') {
      $sql .= "AND $inClause;";
    }

    return $wpdb->get_results($sql, ARRAY_N);
  }

  function findAll() {
    global $wpdb;

    $postType = $this->getName();
    $sql = <<<SQL
    SELECT id from $wpdb->posts
    WHERE post_type = '$postType';
SQL;

    $imgurImages = $wpdb->get_results($sql, ARRAY_N);
    $ids         = array();

    foreach ($imgurImages as $image) {
      array_push($ids, $image[0]);
    }

    return $ids;
  }

  function findOne($id) {
    $options = array(
      'post_type'      => $this->getName(),
      'p'           => $id,
      'paged'          => 1,
      'posts_per_page' => 1
    );

    $query = new \WP_Query($options);
    $posts = $query->get_posts();

    if (count($posts) === 1) {
      return $posts[0];
    } else {
      return false;
    }
  }

  function delete($postId) {
    return wp_delete_post($postId, true);
  }

  function getName() {
    return 'imgur_image';
  }

  function getOptions() {
    return array(
      'public'              => false,
      'exclude_from_search' => true,
      'publicly_queryable'  => false,
      'show_ui'             => false,
      'hierarchical'        => false,
      'rewrite'             => false,
      'query_var'           => false,
      'can_export'          => true,
      'delete_with_user'    => false
    );
  }

  /* helpers */
  function toSlug($postName) {
    return sanitize_title_with_dashes($postName, null, 'save');
  }

  function toJSON($content) {
    return json_encode($content);
  }

  function toImages($content) {
    $images = json_decode($content, true);
    if (is_array($images)) {
      return $images;
    } else {
      return array();
    }
  }

  function inClauseFor($column, $items) {
    global $wpdb;

    $n = count($items);
    if ($n === 0) {
      return '';
    }

    $sql = $column . ' IN (';

    for ($i = 0; $i < $n; $i++) {
      $item = $this->toSlug($items[$i]);
      $sql .= $wpdb->prepare('%s', $item);

      if ($i < ($n - 1)) {
        $sql .= ',';
      }
    }

    $sql .= ')';

    return $sql;
  }

}
