
// initialize ncp_search table once with all tables which has to be indexed in the main function
NCPSearch::insert_ncp_search_items(UserModel::model()->findAll(), "user", ["login", "mail", "name_last", "name_first"]);
NCPSearch::insert_ncp_search_items(DepartmentModel::model()->findAll(), "department", ["title", "description"]);
NCPSearch::insert_ncp_search_items(ObjectModel::model()->findAll(), "object", ["title", "description"]);
  

// for each model  
class Poem : Model
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
  
  
  