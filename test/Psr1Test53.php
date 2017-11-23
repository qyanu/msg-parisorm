<?php

class Psr1Test53 extends PHPUnit_Framework_TestCase {

    public function setUp() {
        // Set up the dummy database connection
        ORM::set_db(new MockPDO('sqlite::memory:'));

        // Enable logging
        ORM::configure('logging', true);
    }

    public function tearDown() {
        ORM::configure('logging', false);
        ORM::set_db(null);
    }

    public function testInsertData() {
        $widget = Model::factory('Simple')->create();
        $widget->name = "Fred";
        $widget->age = 10;
        $widget->save();
        $expected = "INSERT INTO `simple` (`name`, `age`) VALUES (?, ?) {array (  0 => 'Fred',  1 => 10,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testUpdateData() {
        $widget = Model::factory('Simple')->findOne(1);
        $widget->name = "Fred";
        $widget->age = 10;
        $widget->save();
        $expected = "UPDATE `simple` SET `name` = ?, `age` = ? WHERE `id` = ? {array (  0 => 'Fred',  1 => 10,  2 => 1,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testDeleteData() {
        $widget = Model::factory('Simple')->findOne(1);
        $widget->delete();
        $expected = "DELETE FROM `simple` WHERE `id` = ? {array (  0 => 1,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testInsertingDataContainingAnExpression() {
        $widget = Model::factory('Simple')->create();
        $widget->name = "Fred";
        $widget->age = 10;
        $widget->setExpr('added', 'NOW()');
        $widget->save();
        $expected = "INSERT INTO `simple` (`name`, `age`, `added`) VALUES (?, ?, NOW()) {array (  0 => 'Fred',  1 => 10,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testHasOneRelation() {
        $user = Model::factory('User2')->findOne(1);
        $profile = $user->profile()->findOne();
        $expected = "SELECT * FROM `profile2` WHERE `user2_id` = ? LIMIT 1 {array (  0 => 1,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testHasOneWithCustomForeignKeyName() {
        $user2 = Model::factory('UserTwo2')->findOne(1);
        $profile = $user2->profile()->findOne();
        $expected = "SELECT * FROM `profile2` WHERE `my_custom_fk_column` = ? LIMIT 1 {array (  0 => 1,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testBelongsToRelation() {
        $user2 = Model::factory('UserTwo2')->findOne(1);
        $profile = $user2->profile()->findOne();
        $profile->user_id = 1;
        $user3 = $profile->user()->findOne();
        $expected = "SELECT * FROM `user2` WHERE `id` = ? LIMIT 1 {array (  0 => NULL,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testBelongsToRelationWithCustomForeignKeyName() {
        $profile2 = Model::factory('ProfileTwo2')->findOne(1);
        $profile2->custom_user_fk_column = 5;
        $user4 = $profile2->user()->findOne();
        $expected = "SELECT * FROM `user2` WHERE `id` = ? LIMIT 1 {array (  0 => 5,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testHasManyRelation() {
        $user4 = Model::factory('UserThree2')->findOne(1);
        $posts = $user4->posts()->findMany();
        $expected = "SELECT * FROM `post2` WHERE `user_three2_id` = ? {array (  0 => 1,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testHasManyRelationWithCustomForeignKeyName() {
        $user5 = Model::factory('UserFour2')->findOne(1);
        $posts = $user5->posts()->findMany();
        $expected = "SELECT * FROM `post2` WHERE `my_custom_fk_column` = ? {array (  0 => 1,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testHasManyThroughRelation() {
        $book = Model::factory('Book2')->findOne(1);
        $authors = $book->authors()->findMany();
        $expected = "SELECT `author2`.* FROM `author2` JOIN `author2book2` ON `author2`.`id` = `author2book2`.`author2_id` WHERE `author2book2`.`book2_id` = ? {array (  0 => 1,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testHasManyThroughRelationWithCustomIntermediateModelAndKeyNames() {
        $book2 = Model::factory('BookTwo2')->findOne(1);
        $authors2 = $book2->authors()->findMany();
        $expected = "SELECT `author2`.* FROM `author2` JOIN `author_book2` ON `author2`.`id` = `author_book2`.`custom_author_id` WHERE `author_book2`.`custom_book_id` = ? {array (  0 => 1,)}";
        $this->assertEquals($expected, ORM::getLastQuery());
    }
}

class Profile2 extends Model {
    public function user() {
        return $this->belongsTo('User2');
    }
} 
class User2 extends Model {
    public function profile() {
        return $this->hasOne('Profile2');
    }
}
class UserTwo2 extends Model {
    public function profile() {
        return $this->hasOne('Profile2', 'my_custom_fk_column');
    }
}
class ProfileTwo2 extends Model {
    public function user() {
        return $this->belongsTo('User2', 'custom_user_fk_column');
    }
}
class Post2 extends Model { }
class UserThree2 extends Model {
    public function posts() {
        return $this->hasMany('Post2');
    }
}
class UserFour2 extends Model {
    public function posts() {
        return $this->hasMany('Post2', 'my_custom_fk_column');
    }
}
class Author2 extends Model { }
class AuthorBook2 extends Model { }
class Book2 extends Model {
    public function authors() {
        return $this->hasManyThrough('Author2');
    }
}
class BookTwo2 extends Model {
    public function authors() {
        return $this->hasManyThrough('Author2', 'AuthorBook2', 'custom_book_id', 'custom_author_id');
    }
}