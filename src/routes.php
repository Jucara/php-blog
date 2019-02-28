<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/~{domain}[/]', function (Request $request, Response $response, array $args) {
    $articles = $this->db->query('
        SELECT id_article, title, article_date as date, content as text, username as author
        FROM articles
        INNER JOIN users
            ON articles.id_user = users.id_user
        ORDER BY article_date DESC LIMIT 5');
    if(count($articles) > 0) {
        foreach ($articles as &$article) {
            $article['categories'] = array();
        }

        $selectedArticleIds = array();
        for ($i=0; $i < 5 ; $i++) {
            $selectedArticleIds[$i] = $articles[$i%count($articles)]['id_article'];
        }

        $catArticles = $this->db->query('
            SELECT cat_art.id_article, nom_cat
            FROM cat_art
                INNER JOIN articles
                    ON cat_art.id_article = articles.id_article
                INNER JOIN categories
                    ON cat_art.id_cat = categories.id_cat
            WHERE cat_art.id_article IN (?, ?, ?, ?, ?)
        ', $selectedArticleIds);

        foreach ($catArticles as $catArticle) {
            foreach ($articles as &$article) {
                if($catArticle['id_article'] == $article['id_article']){
                    array_push($article['categories'], $catArticle['nom_cat']);
                }
            }
        }
    }

    $nbArticles = $this->db->query('SELECT COUNT(*) FROM articles')[0]['count'];
    $categories = $this->db->query('SELECT * FROM categories');
    $authors = $this->db->query('SELECT username FROM users WHERE permission >= 1')[0];

    $args['articles'] = $articles;
    $args['nbArticles'] = $nbArticles;
    $args['categories'] = $categories;
    $args['authors'] = $authors;

    return ($this->render)($response, 'home.twig', $args);
})->setName('home');

$app->get('/~{domain}/article/{id}', function (Request $request, Response $response, array $args) {

    $article = $this->db->query('
        SELECT title, article_date as date, content as text, username as author
        FROM articles
        INNER JOIN users
            ON articles.id_user = users.id_user
        ORDER BY article_date DESC LIMIT 5
    ')[0];

    if (count($article) == 0) {
        return ($this->notFoundHandler)($request, $response);
    }
    $article['categories'] = array();

    $catArticles = $this->db->query('
        SELECT nom_cat
        FROM cat_art
            INNER JOIN articles
                ON cat_art.id_article = articles.id_article
            INNER JOIN categories
                ON cat_art.id_cat = categories.id_cat
        WHERE cat_art.id_article = ?;
    ', array($args['id']));

    foreach ($catArticles as $catArticle) {
        array_push($article['categories'], $catArticle['nom_cat']);
    }

    $article['comments'] = $this->db->query('
        SELECT comment_text as text, comment_date as date, username as author
        FROM comments
            INNER JOIN articles
                ON comments.id_article = articles.id_article
            INNER JOIN users
                ON comments.id_user = users.id_user
        WHERE comments.id_article = ?;
    ', array($args['id']));

    $args['article'] = $article;
    return ($this->render)($response, 'article.twig', $args);
})->setName('article');

$app->get('/~{domain}/404', function (Request $request, Response $response, array $args) {
    return ($this->render)($response, '404.twig', $args);
})->setName('404');

$app->get('/~{domain}/login', function (Request $request, Response $response, array $args) {
    return ($this->render)($response, 'login.twig', $args);
})->setName('login');

$app->get('/~{domain}/signUp', function (Request $request, Response $response, array $args) {
    return ($this->render)($response, 'signUp.twig', $args);
})->setName('signUp');

//Page de creation d'articles
$app->get('/~{domain}/post', function (Request $request, Response $response, array $args) {
    return ($this->render)($response, 'post.twig', $args);
})->setName('post');

$app->get('/~{domain}/edit/{id}', function (Request $request, Response $response, array $args) {
    $query = false;//$this->db->query(/*Todo*/);
    if ($article){
        $article = $query->fetch(PDO::FETCH_ASSOC);
        $args['article'] = $article;
        return ($this->render)($response, 'edit.twig', $args);
    } else {
        return $response->withRedirect($this->router->pathFor('404', ['domain' => $args['domain']]));
    }
})->setName('edit');

$app->get('/~{domain}/dashboard', function (Request $request, Response $response, array $args) {
    return ($this->render)($response, 'dashboard.twig', $args);
})->setName('dashboard');
