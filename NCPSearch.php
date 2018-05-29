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

