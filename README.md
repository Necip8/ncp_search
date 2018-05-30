ncp_search engine - the basic idea
----------------------------------

Why this post?
--------------
The amount of data increases every day. As a result, the search in the databases becomes longer and longer. Conventional data structures must be realigned in order to be able to access information more quickly.There are already database systems like Elasticsearch that can do this. However, such systems also have disadvantages. There are no advantages without disadvantages! 

The most noticeable major drawbacks are:
- Learning a new query language. SQL won't get you far.
- The existing programs must be rewritten in order to process the new result sets appropriately.
- The safety regulations must be defined again.
- A second database must be set up, which in principle contains the same data.

Who will benefit from this post?
--------------------------------
This information is useful for any programmer who wants to integrate an index database into his existing system in a simple and elegant way without additional effort.

What we will and will not cover?
--------------------------------
...

The basic idea behind Indexing-machines like Elastisearch
---------------------------------------------------------

We will use this simple Table to demonstrate what an Index-machine does

Tablename: object

| UID  | Title             | Description                                                                                |
|------|-------------------|--------------------------------------------------------------------------------------------|
| 4711 | Rudyard Kipling   | If you can keep your head when all about you ...                                           |
| 4712 | John Magee        | I have slipped the surly bonds of Earth and danced the skies on laugher-silvered wings ... |
| 4713 | Wiliam Wordsworth | Ten thousand saw I at a glance, Tossing their heads in sprightly dance...                  |

With this request we can find very specific texts in this single table:

```
SELECT ID, Title, Description
FROM object
WHERE Description like '%head%'
```

But what if we want to find '%head%' in all tables of our database?

We have to write a code to do this job for us.
This is inefficent and will work very slowy.

The idea behind Elasticsearch and other indexing tables is - so far I understood - to break the strings in single tokens.

That means in a very easy way that we have to transform the horicontal order of the table into a vertical order.

Tablename: ncp_index

| UID  | Tablename | Fieldname   | ID   | Token   |
|------|-----------|-------------|------|---------|
| 1001 | Poem      | Description | 4711 | if      |
| 1002 | Poem      | Description | 4711 | you     |
| 1003 | Poem      | Description | 4711 | can     |
| ...  |           |             |      |         |
| 1010 | Poem      | Description | 4712 | I       |
| 1011 | Poem      | Description | 4712 | have    |
| 1012 | Poem      | Description | 4712 | slipped |
| ...  |           |             |      |         |

We can tokenize any field of any table of our database into the table ncp_index.

Now we can find with a single query very fast any (tokenized) word in our hole database.

```
SELECT Tablenname, Fieldname, Token
FROM ncp_index
WHERE Token like '%head%'
```

That is the revealed secret of an Index-Searchengine like Elastisearch.

Yes, the ncp_index table has a lot of redundant data that we can normalize as follows:

* Every field is stored in a system table and has a unique id. let us call it field_id
* Every content of a field has a  lot of same words. These words should be stored only once in a separat words-table.

Our ncp_index table looks now so:

| UID  | Field_id | ID   | Token_id |
|------|----------|------|----------|
| 1001 | 123      | 4711 | 1        |
| 1002 | 123      | 4711 | 2        |
| 1003 | 123      | 4711 | 3        |
| ...  |          |      |          |
| 1010 | 123      | 4712 | 4        |
| 1011 | 123      | 4712 | 5        |
| 1012 | 123      | 4712 | 6        |
| ...  |          |      |          |


Systemtable: fields

| UID | Tablename | Name        | Token_id |
|-----|-----------|-------------|----------|
| 122 | object    | Name        | 1        |
| 123 | object    | Description | 2        |
| ... |           |             |          |

Tablename: word

| UID | Token |
|-----|-------|
| 1   | if    |
| 2   | you   |
| 3   | can   |
|...  |       |


Some basic examples
-------------------

```
<?php
/**
 * @author ncp <necips@live.de>
 */
class NCPSearch
{
    /**
     * @param $model
     * @param $tablename
     * @param $fieldnames
     */
    public static function delete_ncp_search_item($model, $tablename) {
        $criteria = new CDbCriteria;
        $criteria->condition = "tablename = :tablename " .
                    "AND id = :id ";
        $criteria->params[":tablename"] = $tablename;
        $criteria->params[":id"] = $model->uid;
        NCPIndexModel::model()->deleteAll($criteria);
    }
    /**
     * @param $model
     * @param $tablename
     * @param $fieldnames
     */
    public static function update_ncp_search_item($model, $tablename, $fieldnames) {
        NCPSearch::delete_ncp_search_item($model, $tablename);
        NCPSearch::insert_ncp_search_item($model, $tablename, $fieldnames);
    }
    /**
     * @param $model
     * @param $tablename
     * @param $fieldnames
     */
    public static function insert_ncp_search_item($model, $tablename, $fieldnames) {
        foreach ($fieldnames as $fieldname) {
            $NCP_index_model = new NCPIndexModel("create");
            $NCP_index_model->tablename = $tablename;
            $NCP_index_model->fieldname = $fieldname;
            $NCP_index_model->id = $model->uid;
            $NCP_index_model->save();
            // a very simple way to tokenize the strings!
            $raw = strip_tags($model->{$fieldname});
            $tokens = explode( ' ', $raw);
            foreach ($tokens as $token) {
                $NCP_token_model = new NCPTokenModel("create");
                $NCP_token_model->NCP_index_uid = $NCP_index_model->uid;
                $NCP_token_model->token = $token;
                $NCP_token_model->save();
            }
        }
    }
    /**
     * @param $models
     * @param $tablename
     * @param $fieldnames
     */
    public static function insert_ncp_search_items($models, $tablename, $fieldnames) {
        foreach ($models as $model) {
            NCPSearch::insert_ncp_search_item($model, $tablename, $fieldnames);
        }
    }
}


// main.php:

// initialize ncp_search table once with all tables which has to be indexed in the main function
NCPSearch::insert_ncp_search_items(UserModel::model()->findAll(), "user", ["login", "mail", "name_last", "name_first"]);
NCPSearch::insert_ncp_search_items(DepartmentModel::model()->findAll(), "department", ["title", "description"]);
NCPSearch::insert_ncp_search_items(ObjectModel::model()->findAll(), "object", ["title", "description"]);
  

// model.php:

class Object : Model
{
  function afterSave()  {
    
    
     // insert this code to synchronize the informations on ncp_index
     if ($this->status === ObjectStatus::DELETED)
            NCPSearch::delete_ncp_search_item($this, "object");
        else
            NCPSearch::update_ncp_search_item($this, "object", ["title", "description"]);       
            
    ...
  }
  
  ...
  
}
```

Conclusion
----------
These are my basic observations on this subject. They are the first steps to a powerful machine that can index existing tables so that they can be found quickly.

Thanks to
---------
Translated with www.DeepL.com/Translator
Elasticsearch: https://www.elastic.co/blog/a-practical-introduction-to-elasticsearch


Contact
-------
necips@live.de 



