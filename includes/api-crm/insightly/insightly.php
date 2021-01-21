<?php
/**
   Insightly PHP library for Insightly API
   
   This library provides user friendly access to the version 2.1 REST API
   for Insightly.
   
   The library is built using PHP standard libraries.
   (no third party tools required, so it will run out of the box
   on most PHP environments).
   The wrapper functions return native PHP objects (arrays and objects),
   so working with them is easily done using built in functions. 
   
    USAGE:
   
    Simply add insightly.php to your PHP include path,
    then do the following to run a test suite:
   
    require("insightly.php");
    $i = new Insightly('your API key');
    $i->test();
   
    This will run an automatic test suite against your Insightly account.
    If the methods you need all pass, you're good to go!

    For convenience, you can also run test.php from the command line:

    php test.php <your-api-key>

    This will run the test suite as well.
   
    If you are working with very large recordsets,
    you should use ODATA filters to access data in smaller chunks.
    This is a good idea in general to minimize server response times.
   
    BASIC USE PATTERNS:
   
    CREATE/UPDATE ACTIONS
   
    These methods expect an object containing valid data fields for the object.
    They will return a dictionary containing the object
    as stored on the server (if successful)
    or raise an exception if the create/update request fails.
    You indicate whether you want to create a new item
    by setting the record id to 0 or omitting it.
   
    To obtain sample objects, you can do the following:
   
    $contact = $i->addContact('sample');
    $event = $i->addEvent('sample');
    $organization = $i->addOrganization('sample');
    $project = $i->addProject('sample');
   
    This will return a random item from your account,
    so you can see what fields are required,
    along with representative field values.
   
    SEARCH ACTIONS
   
    These methods return a list of dictionaries containing the matching items.
    For example to request a list of all contacts, you call:

    $contacts = $i->getContacts()
   
    SEARCH ACTIONS USING ODATA
   
    Search methods recognize top, skip, orderby and filters parameters,
    which you can use to page, order and filter recordsets.
    These are passed via an associative array:

    // get the first 200 contacts   
    $contacts = $i->getContacts(array("top" => 200));

    // get the first 200 contacts, in first name descending order
    $contacts = $i->getContacts(array("orderby" => 'FIRST_NAME desc', "top" => 200));

    // get 200 records, after skipping the first 200 records
    $contacts = $i->getContacts(array("top" => 200, "skip" => 200));

    // get contacts where FIRST_NAME='Brian'
    $contacts = $i->getContacts(array("filters" => array('FIRST_NAME=\'Brian\'')));
   
    IMPORTANT NOTE: when using OData filters,
    be sure to include escaped quotes around the search term.
    Otherwise you will get a 400 (bad request) error.
   
    These methods will raise an exception if the lookup fails,
    or return a (possibly empty) list of objects if successful.
   
    READ ACTIONS (SINGLE ITEM)
   
    These methods will return a single object containing the requested item's properties.

    $contact = $i->getContact(123456);
   
    DELETE ACTIONS
   
    These methods will return True if successful, or raise an exception.
    e.g. $success = $i->deleteContact(123456)
   
    IMAGE AND FILE ATTACHMENT MANAGEMENT
   
    The API calls to manage images and file attachments have not yet been implemented in the PHP library. However you can access
    these directly via our REST API
   
    ISSUES TO BE AWARE OF
   
    This library makes it easy to integrate with Insightly,
    and by automating HTTPS requests for you,
    eliminates the most common causes of user issues.
    That said, the service is picky about rejecting requests
    that do not have required fields, or have invalid field values
    (such as an invalid USER_ID).
    When this happens, you'll get a 400 (bad request) error.
    Your best bet at this point is to consult the
    API documentation and look at the required request data.
   
    Write/update methods also have a dummy feature
    that returns sample objects that you can use as a starting point.
    For example, to obtain a sample task object, just call:
   
    $task = $i->addTask('sample');
   
    This will return one of the tasks from your Insightly account,
    so you can get a sense of the fields and values used.
   
    If you are working with large recordsets,
    we strongly recommend that you use ODATA functions,
    such as top and skip to page through recordsets
    rather than trying to fetch entire recordsets in one go.
    This both improves client/server communication,
    but also minimizes memory requirements on your end.
    
    TROUBLESHOOTING TIPS
    
    One of the main issues API users run into during write/update operations
    is a 400 error (bad request) due to missing required fields.
    If you are unclear about what the server is expecting,
    a good way to troubleshoot this is to do the following:
    
    * Using the web interface, create the object in question
      (contact, project, team, etc), and add sample data and child elements to it
    * Use the corresponding getNNNN() method to retrieve this object via the web API
    * Inspect the object's contents and structure
    
    Read operations via the API are generally quite straightforward,
    so if you get struck on a write operation, this is a good workaround,
    as you are probably just missing a required field
    or using an invalid element ID when referring
    to something such as a link to a contact.
*/
class Insightly{
  /**
   * API key
   * 
   * @var string
   */
  private $apikey;

  /**
   * Class constructor accepting an API key
   * 
   * @param string $apikey
   */
  public function __construct($apikey){
    $this->apikey = $apikey;
  }

  /**
   * Gets a list of contacts 
   * 
   * @param array $options
   * @return mixed
   * @link https://api.insight.ly/v2.1/Help/Api/GET-Contacts_ids_email_tag
   */
  public function getContacts($options = null){
    $email = isset($options["email"]) ? $options["email"] : null;
    $tag = isset($options["tag"]) ? $options["tag"] : null;
    $ids = isset($options["ids"]) ? $options["ids"] : null;

    $request = $this->GET("/v2.1/Contacts");

    // handle standard OData options
    $this->buildODataQuery($request, $options);

    // handle other options
    if($email != null){
      $request->queryParam("email", $email);
    }
    if($tag != null){
      $request->queryParam("tag", $tag);
    }
    if($ids != null){
      $s = "";
      foreach($ids as $key => $value){
        if($key > 0){
          $s = $s . ",";
        }
        $s = $s . $value;
      }
      $request->queryParam("ids", $s);
    }

    return $request->asJSON();
  }

  /**
   * Gets a contact
   * 
   * @param int $id
   * @return mixed
   * @link https://api.insight.ly/v2.1/Help/Api/GET-Contacts-id
   */
  public function getContact($id){
    return $this->GET("/v2.1/Contacts/" . $id)->asJSON();
  }

  /**
   * Adds a contact
   * 
   * @param stdClass $contact
   * @return mixed
   * @link https://api.insight.ly/v2.1/Help/Api/POST-Contacts
   */
  public function addContact($contact){
    $url_path = "/v2.1/Contacts";
    $request = null;

    if(isset($contact->CONTACT_ID) && $contact->CONTACT_ID > 0){
      $request = $this->PUT($url_path);
    }
    else{
      $request = $this->POST($url_path);
    }

    return $request->body($contact)->asJSON();
  }

  public function deleteContact($id){
    $this->DELETE("/v2.1/Contacts/$id")->asString();
    return true;
  }

  public function getContactEmails($contact_id){
    return $this->GET("/v2.1/Contacts/$contact_id/Emails")->asJSON();
  }

  public function getContactNotes($contact_id){
    return $this->GET("/v2.1/Contacts/$contact_id/Notes")->asJSON();
  }

  public function getContactTasks($contact_id){
    return $this->GET("/v2.1/Contacts/$contact_id/Tasks")->asJSON();
  }

  public function getCountries(){
    return $this->GET("/v2.1/Countries")->asJSON();
  }

  public function getCurrencies(){
    return $this->GET("/v2.1/Currencies")->asJSON();
  }

  public function getCustomFields(){
    return $this->GET("/v2.1/CustomFields")->asJSON();
  }

  public function getCustomField($id){
    return $this->GET("/v2.1/CustomFields/$id")->asJSON();
  }

  public function getEmails($options = null){
    $request = $this->GET("/v2.1/Emails");
    $this->buildODataQuery($request, $options);
    return $request->asJSON();
  }

  public function getEmail($id){
    return $this->GET("/v2.1/Emails/$id")->asJSON();
  }

  public function deleteEmail($id){
    $this->DELETE("/v2.1/Emails/$id")->asString();
    return true;
  }

  public function getEmailComments($email_id){
    $this->GET("/v2.1/Emails/$email_id/Comments")->asJSON();
  }

  public function addCommentToEmail($email_id, $body, $owner_user_id){
    $data = new stdClass();
    $data->BODY = $body;
    $data->OWNER_USER_ID = $owner_user_id;
    return $this->POST("/v2.1/Emails/")->body($data)->asJSON();
  }

  public function getEvents($options = null){
    $request = $this->GET("/v2.1/Events");
    $this->buildODataQuery($request, $options);
    return $request->asJSON();
  }

  public function getEvent($id){
    return $this->GET("/v2.1/Events/$id")->asJSON();
  }

  public function addEvent($event){
    if($event == "sample"){
      $return = $this->getEvents(array("top" => 1));
      return $return[0];
    }

    $url_path = "/v2.1/Events";
    if(isset($event->EVENT_ID) && ($event->EVENT_ID > 0)){
      $request = $this->PUT($url_path);
    }
    else{
      $request = $this->POST($url_path);
    }

    return $request->body($event)->asJSON();
  }

  public function deleteEvent($id){
    $this->DELETE("/v2.1/Events/$id")->asString();
    return true;
  }

  public function getFileCategories(){
    return $this->GET("/v2.1/FileCategories")->asJSON();
  }

  public function getFileCategory($id){
    return $this->GET("/v2.1/FileCategories/$id")->asJSON();
  }

  public function addFileCategory($category){
    if($category == "sample"){
      $return = $this->getFileCategories();
      return $return[0];
    }

    $url_path = "/v2.1/FileCategories";
    if(isset($category->CATEGORY_ID)){
      $request = $this->PUT($url_path);
    }
    else{
      $request = $this->POST($url_path);
    }

    return $request->body($category)->asJSON();
  }

  public function deleteFileCategory($id){
    $this->DELETE("/v2.1/FileCategories/$id")->asString();
    return true;
  }

  public function getNotes($options = null){
    $request = $this->GET("/v2.1/Notes");
    $this->buildODataQuery($request, $options);
    return $request->asJSON();
  }

  public function getNote($id){
    return $request = $this->GET("/v2.1/Notes/$id")->asJSON();
  }

  public function addNote($note){
    if($note == "sample"){
      $return = $this->getNotes(array("top" => 1));
      return $return[0];

    }

    $url_path = "/v2.1/Notes";
    if(isset($note->NOTE_ID) && ($note->NOTE_ID > 0)){
      $request = $this->PUT($url_path);
    }
    else{
      $request = $this->POST($url_path);
    }

    return $request->body($note)->asJSON();
  }

  public function getNoteComments($note_id){
    return $this->GET("/v2.1/Notes/$note_id/Comments")->asJSON();
  }

  public function addNoteComment($note_id, $comment){
    if($comment == "sample"){
      $comment = new stdClass();
      $comment->COMMENT_ID = 0;
      $comment->BODY = "This is a comment.";
      $comment->OWNER_USER_ID = 1;
      $comment->DATE_CREATED_UTC = "2014-07-15 16:40:00";
      $comment->DATE_UPDATED_UTC = "2014-07-15 16:40:00";
      return $comment;
    }

    return $this->POST("/v2.1/$note_id/Comments")->body($comment)->asJSON();
  }

  public function deleteNote($id){
    $this->DELETE("/v2.1/Notes/$id")->asString();
    return true;
  }

  public function getOpportunities($options = null){
    $request = $this->GET("/v2.1/Opportunities");
    $this->buildODataQuery($request, $options);
    return $request->asJSON();
  }

  public function getOpportunity($id){
    return $this->GET("/v2.1/Opportunities/" . $id)->asJSON();
  }

  public function addOpportunity($opportunity){
    if($opportunity == "sample"){
      $return = $this->getOpportunities(array("top" => 1));
      return $return[0];
    }

    $url_path = "/v2.1/Opportunities";

    if(isset($opportunity->OPPORTUNITY_ID) && ($opportunity->OPPORTUNITY_ID > 0)){
      $request = $this->PUT($url_path);
    }
    else{
      $request = $this->POST($url_path);
    }

    return $request->body($opportunity)->asJSON();
  }

  public function deleteOpportunity($id){
    $this->DELETE("/v2.1/Opportunities/$id")->asString();
    return true;
  }

  public function getOpportunityEmails($opportunity_id){
    return $this->GET("/v2.1/Opportunities/$opportunity_id/Emails")->asJSON();
  }

  public function getOpportunityNotes($opportunity_id){
    return $this->GET("/v2.1/Opportunities/$opportunity_id/Notes")->asJSON();
  }

  public function getOpportunityStateHistory($opportunity_id){
    return $this->GET("/v2.1/Opportunities/$opportunity_id/StateHistory")->asJSON();
  }

  public function getOpportunityTasks($opportunity_id){
    return $this->GET("/v2.1/Opportunities/$opportunity_id/Tasks")->asJSON();
  }

  public function getOpportunityCategories(){
    return $this->GET("/v2.1/OpportunityCategories")->asJSON();
  }

  public function getOpportunityCategory($id){
    return $this->GET("/v2.1/OpportunityCategories/$id")->asJSON();
  }

  public function addOpportunityCategory($category){
    if($category == "sample"){
      $return = $this->getOpportunityCategories();
      return $return[0];
    }

    $url_path = "/v2.1/OpportunityCategories";
    if(isset($category->CATEGORY_ID) && ($category->CATEGORY_ID > 0)){
      $request = $this->PUT($url_path);
    }
    else{
      $request = $this->POST($url_path);
    }

    return $request->body($category)->asJSON();
  }

  public function deleteOpportunityCategory($id){
    $this->DELETE("/v2.1/OpportunityCategories/$id")->asString();
    return true;
  }

  public function getOpportunityStateReasons(){
    return $this->GET("/v2.1/OpportunityStateReasons")->asJSON();
  }

  public function getOrganizations($options = null){
    $request = $this->GET("/v2.1/Organisations");
    $this->buildODataQuery($request, $options);
    return $request->asJSON();
  }

  public function getOrganization($id){
    return $this->GET("/v2.1/Organisations/$id")->asJSON();
  }

  public function addOrganization($organization){
    if($organization == "sample"){
      $return = $this->getOrganizations(array("top" => 1));
      return $return[0];
    }

    $url_path = "/v2.1/Organisations";
    if(isset($organization->ORGANISATION_ID) && ($organization->ORGANISATION_ID > 0)){
      $request = $this->PUT($url_path);
    }
    else{
      $request = $this->POST($url_path);
    }

    return $request->body($organization)->asJSON();
  }

  public function deleteOrganization($id){
    $this->DELETE("/v2.1/Organisations/$id")->asString();
    return true;
  }

  public function getOrganizationEmails($organization_id){
    return $this->GET("/v2.1/Organisations/$organization_id/Emails")->asJSON();
  }

  public function getOrganizationNotes($organization_id){
    return $this->GET("/v2.1/Organisations/$organization_id/Notes")->asJSON();
  }

  public function getOrganizationTasks($organization_id){
    return $this->GET("/v2.1/Organisations/$organization_id/Tasks")->asJSON();
  }

  public function getPipelines(){
    return $this->GET("/v2.1/Pipelines")->asJSON();
  }

  public function getPipeline($id){
    return $this->GET("/v2.1/Pipelines/$id")->asJSON();
  }

  public function getPipelineStages(){
    return $this->GET("/v2.1/PipelineStages")->asJSON();
  }

  public function getPipelineStage($id){
    return $this->GET("/v2.1/PipelineStages/$id")->asJSON();
  }

  public function getProjectCategories(){
    return $this->GET("/v2.1/ProjectCategories")->asJSON();
  }

  public function getProjectCategory($id){
    return $this->GET("/v2.1/ProjectCategories/$id")->asJSON();
  }

  public function addProjectCategory($category){
    if($category == "sample"){
      $return = $this->getProjectCategoriest();
      return $return[0];
    }

    $url_path = "/v2.1ProjectCategories";
    if(isset($category->CATEGORY_ID) && ($category->CATEGORY_ID > 0)){
      $request = $this->PUT($url_path);
    }
    else{
      $request = $this->POST($url_path);
    }

    return $request->body($category)->asJSON();
  }

  public function deleteProjectCategory($id){
    $this->DELETE("/v2.1/ProjectCategories/$id")->asString();
    return true;
  }

  public function getProjects($options = null){
    $tag = isset($options["tag"]) ? $options["tag"] : null;
    $ids = isset($options["ids"]) ? $options["ids"] : null;
      
    $request = $this->GET("/v2.1/Projects");

    // handle standard OData options
    $this->buildODataQuery($request, $options);

    // handle other options
    if($tag != null){
      $request->queryParam("tag", $tag);
    }
    if($ids != null){
      $s = "";
      foreach($ids as $key => $value){
        if($key > 0){
          $s = $s . ",";
        }
        $s = $s . $value;
      }
      $request->queryParam("ids", $s);
    }

    return $request->asJSON();
  }

  public function getProject($id){
    return $this->GET("/v2.1/Projects/$id")->asJSON();
  }

  public function addProject($project){
    if($project == "sample"){
      $return = $this->getProjects();
      return $return[0];

    }

    $url_path = "/v2.1/Projects";
    if(isset($project->PROJECT_ID) && ($project->PROJECT_ID > 0)){
      $request = $this->PUT($url_path);
    }
    else{
      $request = $this->POST($url_path);
    }

    return $request->body($project)->asJSON();
  }

  public function deleteProject($id){
    $this->DELETE("/v2.1/Projects/$id")->asString();
    return true;
  }

  public function getProjectEmails($project_id){
    return $this->GET("/v2.1/Projects/$project_id/Emails")->asJSON();
  }

  public function getProjectNotes($project_id){
    return $this->GET("/v2.1/Projects/$project_id/Notes")->asJSON();
  }

  public function getProjectTasks($project_id){
    return $this->GET("/v2.1/Projects/$project_id/Tasks")->asJSON();
  }

  public function getRelationships(){
    return $this->GET("/v2.1/Relationships")->asJSON();
  }

  public function getTags($id){
    return $this->GET("/v2.1/Tags/$id")->asJSON();
  }

  public function getTasks($options = null){
    $request = $this->GET("/v2.1/Tasks");
    $this->buildODataQuery($request, $options);

    if(isset($options["ids"])){
      $ids = "";
      foreach($options["ids"] as $id){
        $ids .= $id . ",";
      }
      $request.queryParam("ids", $ids);
    }

    return $request->asJSON();
  }

  public function getTask($id){
    return $this->GET("/v2.1/Tasks/$id")->asJSON();
  }

  public function addTask($task){
    if($task == "sample"){
      $return = $this->getTasks(array("top" => 1));
      return $return[0];
    }

    $url_path = "/v2.1/Tasks";
    if(isset($task->TASK_ID) && ($task->TASK_ID > 0)){
      $request = $this->PUT($url_path);
    }
    else{
      $request = $this->POST($url_path);
    }

    return $request->body($task)->asJSON();
  }

  public function deleteTask($id){
    $this->DELETE("/v2.1/Tasks/$id")->asString();
    return true;
  }

  public function getTaskComments($task_id){
    return $this->GET("/v2.1/Tasks/$task_id/Comments")->asJSON();
  }

  public function addTaskComment($task_id, $comment){
    return $this->POST("/v2.1/Tasks/$task_id/Comments")->body($comment)->asJSON();
  }

  public function getTeams($options = null){
    $request = $this->GET("/v2.1/Teams");
    $this->buildODataQuery($request, $options);
    return $request->asJSON();
  }

  public function getTeam($id){
    return $this->GET("/v2.1/Teams/$id")->asJSON();
  }

  public function addTeam($team){
    if($team == "sample"){
      $return = $this->getTeams(array("top" => 1));
      return $return[0];
    }

    $url_path = "/v2.1/Teams";
    if(isset($team->TEAM_ID) && ($team->TEAM_ID > 0)){
      $request = $this->PUT($url_path);
    }
    else{
      $request = $this->POST($url_path);
    }

    return $request->body($team)->asJSON();
  }

  public function deleteTeam($id){
    $this->DELETE("/v2.1/Teams/$id")->asString();
    return true;
  }

  public function getTeamMembers($team_id){
    return $this->POST("/v2.1/TeamMembers/teamid=$team_id")->asJSON();
  }

  public function getTeamMember($id){
    return $this->POST("/v2.1/TeamMembers/$id")->asJSON();
  }

  public function addTeamMember($team_member){
    if($team_member == "sample"){
      $team_member = new stdClass();
      $team_member->PERMISSION_ID = 1;
      $team_member->TEAM_ID = 1;
      $team_member->MEMBER_USER_ID = 1;
      $team_member->MEMBER_TEAM_ID = 1;
      return $team_member;
    }

    return $this->POST("/v2.1/TeamMembers")->body($team_member)->asJSON();
  }

  public function updateTeamMember($team_member){
    return $this->PUT("/v2.1/TeamMembers")->body($team_member)->asJSON();
  }

  public function deleteTeamMember($id){
    $this->DELETE("/v2.1/TeamMembers/$id")->asString();
    return true;
  }

  public function getUsers(){
    return $this->GET("/v2.1/Users")->asJSON();
  }

  public function getUser($id){
    return $this->GET("/v2.1/Users/" . $id)->asJSON();
  }

  /**
   * Add OData query filters to a request
   * 
   * Accepted options:
   *  - top
   *  - skip
   *  - orderby
   *  - an array of filters 
   * 
   * @param InsightlyRequest $request
   * @param array $options
   * @return InsightlyRequest
   * @link http://www.odata.org/documentation/odata-version-2-0/uri-conventions/
   */
  private function buildODataQuery($request, $options){
      $top = isset($options["top"]) ? $options["top"] : null;
      $skip = isset($options["skip"]) ? $options["skip"] : null;
      $orderby = isset($options["orderby"]) ? $options["orderby"] : null;
      $filters = isset($options["filters"]) ? $options["filters"] : null;

    if($top != null){
      $request->queryParam('$top', $top);
    }
    if($skip != null){
      $request->queryParam('$skip', $skip);
    }
    if($orderby != null){
      $request->queryParam('$orderby', $orderby);
    }
    if($filters != null){
      foreach($filters as $filter){
        $filterValue = str_replace(array('=', '>', '<'),
                                   array(' eq ', ' gt ', ' lt '),
                                   $filter);
        $request->queryParam('$filter', $filterValue);
      }
    }

    return $request;
  }

  /**
   * Create GET request
   * 
   * @param string $url_path
   * @return InsightlyRequest
   */
  private function GET($url_path){
    return new InsightlyRequest("GET", $this->apikey, $url_path);
  }

  /**
   * Create PUT request
   * 
   * @param string $url_path
   * @return InsightlyRequest
   */
  private function PUT($url_path){
    return new InsightlyRequest("PUT", $this->apikey, $url_path);
  }

  /**
   * Create POST request
   * 
   * @param string $url_path
   * @return InsightlyRequest
   */
  private function POST($url_path){
    return new InsightlyRequest("POST", $this->apikey, $url_path);
  }

  /**
   * Create DELETE request
   * 
   * @param string $url_path
   * @return InsightlyRequest
   */
  private function DELETE($url_path){
    return new InsightlyRequest("DELETE", $this->apikey, $url_path);
  }

  /**
   * Test all API library funtions
   * 
   * @param int $top (Number of results in some requests)
   * @throws Exception
   */
  public function test($top=null){
    echo "Test API .....\n";

    echo "Testing authentication\n";

    $passed = 0;
    $failed = 0;

    $currencies = $this->getCurrencies();
    if(count($currencies) > 0){
      echo "Authentication passed...\n";
      $passed += 1;
    }
    else{
      $failed += 1;
    }

    // Test getUsers()
    try{
      $users = $this->getUsers();
      $user = $users[0];
      $user_id = $user->USER_ID;
      echo "PASS: getUsers(), found " . count($users) . " users.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      $user = null;
      $users = null;
      $user_id = null;
      echo "FAIL: getUsers()\n";
      $failed += 1;
    }

    // Test getContacts()
    try{
      $contacts = $this->getContacts(array("orderby" => "DATE_UPDATED_UTC desc",
                                           "top" => $top));
      $contact = $contacts[0];
      echo "PASS: getContacts(), found " . count($contacts) . " contacts.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getContacts()\n";
      $failed += 1;
    }

    if($contact != null){
      $contact_id = $contact->CONTACT_ID;
      try{
        $emails = $this->getContactEmails($contact_id);
        echo "PASS: getContactEmails(), found " . count($emails) . " emails.\n";
        $passed += 1;
      }
      catch(Exception $ex){
        echo "FAIL: getContactEmails()\n";
        $failed += 1;
      }

      try{
        $notes = $this->getContactNotes($contact_id);
        echo "PASS: getContactNotes(), found " . count($notes) . " notes.\n";
        $passed += 1;
      }
      catch(Exception $ex){
        echo "FAIL: getContactNotes()\n";
        $failed += 1;
      }

      try{
        $tasks = $this->getContactTasks($contact_id);
        echo "PASS: getContactTasks(), found " . count($tasks) . " tasks.\n";
        $passed += 1;
      }
      catch(Exception $ex){
        echo "FAIL: getContactTasks()\n";
        $failed += 1;
      }
    }

    // Test addContact()
    try{
      $contact = (object)array("SALUTATION" => "Mr",
                               "FIRST_NAME" => "Testy",
                               "LAST_NAME" => "McTesterson");
      $contact = $this->addContact($contact);
      echo "PASS: addContact()\n";
      $passed += 1;

      // Test deleteContact()
      try{
        $this->deleteContact($contact->CONTACT_ID);
        echo "PASS: deleteContact()\n";
        $passed += 1;
      }
      catch(Exception $ex){
        echo "FAIL: deleteContact()\n";
        $failed += 1;
      }
    }
    catch(Exception $ex){
      $contact = null;
      echo "FAIL: addContact()\n";
      $failed += 1;
    }

    try{
      $countries = $this->getCountries();
      echo "PASS: getCountries(), found " . count($countries) . " countries.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getCountries()\n";
      $failed += 1;
    }

    try{
      $currencies = $this->getCurrencies();
      echo "PASS: getCurrencies(), found " . count($currencies) . " currencies\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getCurrencies()\n";
      $failed += 1;
    }

    try{
      $customfields = $this->getCustomFields();
      echo "PASS: getCustomFields(), found " . count($customfields) . " custom fields.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getCustomFields()\n";
      $failed += 1;
    }

    // Test getEmails()
    try{
      $emails = $this->getEmails(array("top" => $top));
      echo "PASS: getEmails(), found " . count($emails) . " emails.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getEmails()\n";
      $failed += 1;
    }

    // Test getEvents()
    try{
      $events = $this->getEvents(array("top" => $top));
      echo "PASS: getEvents(), found " . count($events) . " events.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getEvents()\n";
      $failed += 1;
    }

    // Test addEvent()
    try{
      $event = (object)array("TITLE" => "Test Event",
                             "LOCATION" => "Somewhere",
                             "DETAILS" => "Details",
                             "START_DATE_UTC" => "2014-07-12 12:00:00",
                             "END_DATE_UTC" => "2014-07-12 13:00:00",
                             "OWNER_USER_ID" => $user_id,
                             "ALL_DAY" => false,
                             "PUBLICLY_VISIBLE" => true);
      $event = $this->addEvent($event);
      echo "PASS: addEvent()\n";
      $passed += 1;

      // Test deleteEvent()
      try{
        $this->deleteEvent($event->EVENT_ID);
        echo "PASS: deleteEvent()\n";
        $passed += 1;
      }
      catch(Exception $ex){
        echo "FAIL: deleteEvent()\n";
        $failed += 1;
      }
    }
    catch(Exception $ex){
      $event = null;
      echo "FAIL: addEvent\n";
      $failed += 1;
    }

    // Test getFileCategories()
    try{
      $categories = $this->getFileCategories();
      echo "PASS: getFileCategories(), found " . count($categories) . " categories\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getFileCategories()\n";
      $failed += 1;
    }

    // Test addFileCategory()
    try{
      $category = new stdClass();
      $category->CATEGORY_NAME = "Test Category";
      $category->ACTIVITY = true;
      $category->BACKGROUND_COLOR = "000000";

      $category = $this->addFileCategory($category);
      echo "PASS: addFileCategory()\n";
      $passed += 1;

      // Test deleteFileCategory()
      try{
        $this->deleteFileCategory($category->CATEGORY_ID);
        echo "PASS: deleteFileCategory()\n";
        $passed += 1;
      }
      catch(Exception $ex){
        echo "FAIL: deleteFileCategory()\n";
        $failed += 1;
      }
    }
    catch(Exception $ex){
      $category = null;
      echo "FAIL: addFileCategory()\n";
      $failed += 1;
    }

    // Test getNotes()
    try{
      $notes = $this->getNotes(array());
      echo "PASS: getNotes(), found " . count($notes) . " notes.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getNotes\n";
      $failed += 1;
    }

    // Test getOpportunities()
    try{
      $opportunities = $this->getOpportunities(array("orderby" => "DATE_UPDATED_UTC desc",
                                                     "top" => $top));
      echo "PASS: getOpportunities(), found " . count($opportunities) . " opportunities.\n";
      $passed += 1;

      if(!empty($opportunities)){
        $opportunity = $opportunities[0];
        $opportunity_id = $opportunity->OPPORTUNITY_ID;

        // Test getOpportunityEmails()
        try{
          $emails = $this->getOpportunityEmails($opportunity_id);
          echo "PASS: getOpportunityEmails(), found " . count($emails) . " emails.\n";
          $passed += 1;
        }
        catch(Exception $ex){
          echo "FAIL: getOpportunityEmails()\n";
          $failed += 1;
        }

        // Test getOpportunityNotes()
        try{
          $notes = $this->getOpportunityNotes($opportunity_id);
          echo "PASS: getOpportunityNotes(), found " . count($notes) . " notes.\n";
          $passed += 1;
        }
        catch(Exception $ex){
          echo "FAIL: getOpportunityNotes()\n";
          $failed += 1;
        }

        // Test getOpportunityTasks()
        try{
          $tasks = $this->getOpportunityTasks($opportunity_id);
          echo "PASS: getOpportunityTasks(), found " . count($tasks) . " tasks.\n";
          $passed += 1;
        }
        catch(Exception $ex){
          echo "FAIL: getOpportunityTasks()\n";
          $failed += 1;
        }

        // Test getOpportunityStateHistory()
        try{
          $states = $this->getOpportunityStateHistory($opportunity_id);
          echo "PASS: getOpportunityStateHistory(), found " . count($states) . " states in history.\n";
          $passed += 1;
        }
        catch(Exception $ex){
          echo "FAIL: getOpportunityStateHistory()\n";
          $failed += 1;
        }
      }
    }
    catch(Exception $ex){
      echo "FAIL: getOpportunities()\n";
      $failed += 1;
    }

    // Test getOpportunityCategories()
    try{
      $categories = $this->getOpportunityCategories();
      echo "PASS: getOpportunityCategories(), found " . count($categories) . "categories.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getOpportunityCategories()\n";
      $failed += 1;
    }

    // Test addOpportunityCategory()
    try{
      $category = new stdClass();
      $category->CATEGORY_NAME="Test Category";
      $category->ACTIVE = true;
      $category->BACKGROUND_COLOR = "000000";

      $category = $this->addOpportunityCategory($category);
      echo "PASS: getOpportunityCategory()\n";
      $passed += 1;

      // Test deleteOpportunityCategory
      try{
        $this->deleteOpportunityCategory($category->CATEGORY_ID);
        echo "PASS: deleteOpportunityCategory()\n";
        $passed += 1;
      }
      catch(Exception $ex){
        echo "FAIL: deleteOpportunityCategory()\n";
        $failed += 1;
      }
    }
    catch(Exception $ex){
      echo "FAIL: addOpportunityCategory()\n";
      $failed += 1;
    }

    // Test getOpportunityStateReasons()
    try{
      $reasons = $this->getOpportunityStateReasons();
      echo "PASS: getOpportunityStateReasons(), found " . count($reasons) . " reasons.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getOpportunityStateReasons()\n";
      $failed += 1;
    }

    // Test getOrganizations()
    try{
      $organizations = $this->getOrganizations(array("top" => $top,
                                                     "orderby" => "DATE_UPDATED_UTC desc"));
      echo "PASS: getOrganizations(), found " . count($organizations) . " organizations.\n";
      $passed += 1;

      if(!empty($organizations)){
        $organization = $organizations[0];
        $organization_id = $organization->ORGANISATION_ID;

        // Test getOrganizationEmails()
        try{
          $emails = $this->getOrganizationEmails($organization_id);
          echo "PASS: getOrganizationEmails(), found " . count($emails) . " emails.\n";
          $passed += 1;
        }
        catch(Exception $ex){
          echo "FAIL: getOrganizationEmails()\n";
          $failed += 1;
        }

        // Test getOrganizationNotes()
        try{
          $notes = $this->getOrganizationNotes($organization_id);
          echo "PASS: getOrganizationNotes(), found " . count($notes) . " notes.\n";
          $passed += 1;
        }
        catch(Exception $ex){
          echo "FAIL: getOrganizationNotes()\n";
          $failed += 1;
        }

        // Test getOrganizationTasks()
        try{
          $tasks = $this->getOrganizationTasks($organization_id);
          echo "PASS: getOrganizationTasks(), found " . count($tasks) . " tasks.\n";
          $passed += 1;
        }
        catch(Exception $ex){
          echo "FAIL: getOrganizationTasks()\n";
          $failed += 1;
        }
      }
    }
    catch(Exception $ex){
      echo "FAIL: getOgranizations()\n";
      $failed += 1;
    }

    // Test addOrganization()
    try{
      $organization = new stdClass();
      $organization->ORGANISATION_NAME = "Foo Corp";
      $organization->BACKGROUND = "Details";

      $organization = $this->addOrganization($organization);
      echo "PASS: addOrganization()\n";
      $passed += 1;

      // Test deleteOrganization()
      try{
        $this->deleteOrganization($organization->ORGANISATION_ID);
        echo "PASS: deleteOrganization()\n";
        $passed += 1;
      }
      catch(Exception $ex){
        echo "FAIL: deleteOrganization()\n";
        $failed += 1;
      }
    }
    catch(Exception $ex){
      echo "FAIL: addOrganization()\n";
      $failed += 1;
    }

    // Test getPipelines()
    try{
      $pipelines = $this->getPipelines();
      echo "PASS: getPipelines(), found " . count($pipelines) . " pipelines\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getPilelines()\n";
      $failed += 1;
    }

    // Test getProjects()
    try{
      $projects = $this->getProjects(array("top" => $top,
                                           "orderby" => "DATE_UPDATED_UTC desc"));
      echo "PASS: getProjects(), found " . count($projects) . " projects.\n";
      $passed += 1;

      if(!empty($projects)){
        $project = $projects[0];
        $project_id = $project->PROJECT_ID;

        // Test getProjectEmails()
        try{
          $emails = $this->getProjectEmails($project_id);
          echo "PASS: getProjectEmails(), found " . count($emails) . " emails.\n";
          $passed += 1;
        }
        catch(Exception $ex){
          echo "FAIL: getProjectEmails()\n";
          $failed += 1;
        }

        // Test getProjectNotes()
        try{
          $notes = $this->getProjectNotes($project_id);
          echo "PASS: getProjectNotes(), found " . count($notes) . " notes.\n";
          $passed += 1;
        }
        catch(Exception $ex){
          echo "FAIL: getProjectNotes()\n";
          $failed += 1;
        }

        // Test getProjectTasks()
        try{
          $tasks = $this->getProjectTasks($project_id);
          echo "PASS: getProjectTasks(), found " . count($tasks) . " tasks.\n";
          $passed += 1;
        }
        catch(Exception $ex){
          echo "FAIL: getProjectTasks()\n";
          $failed += 1;
        }
      }
    }
    catch(Exception $ex){
      echo "FAIL: getProjects()\n";
      $failed += 1;
    }

    // Test getProjectCategories()
    try{
      $categories = $this->getProjectCategories();
      echo "PASS: getProjectCategories(), found " . count($categories) . " categories.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getProjectCategories()\n";
      $failed += 1;
    }

    // Test getRelationships
    try{
      $relationships = $this->getRelationships();
      echo "PASS: getRelationships(), found " . count($relationships) . " relationships.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getRelationships()\n";
      $failed += 1;
    }

    // Test getTasks()
    try{
      $tasks = $this->getTasks(array("top" => $top,
                                     "orderby" => "DUE_DATE desc"));
      echo "PASS: getTasks(), found " . count($tasks) . " tasks.\n";
      $passed += 1;
    }
    catch(Exception $ex){
      echo "FAIL: getTasks()\n";
      $failed += 1;
    }

    // Test getTeams()
    try{
      $teams = $this->getTeams();
      echo "PASS: getTeams(), found " . count($teams) . " teams.\n";
      $passed += 1;

      if(!empty($teams)){
        $team = $teams[0];
        $team_id = $team->TEAM_ID;

        // Test getTeamMembers()
        try{
          $team_members = $this->getTeamMembers($team_id);
          echo "PASS: getTeamMembers(), found " . count($team_members) . " team members.\n";
          $passed += 1;
        }
        catch(Exception $ex){
          echo "FAIL: getTeamMembers()\n";
          $failed += 1;
        }
      }
    }
    catch(Exception $ex){
      echo "FAIL: getTeams()\n";
      $failed += 1;
    }

    if($failed > 0){
      throw new Exception($failed . " tests failed!");
    }
  }
}

/**
 * API Requests class
 * 
 * Helper class for executing REST requests to the Insightly API.
 * 
 * Usage:
 *    - Instanciate: $request = new InsightlyRequest('GET', $apikey, 'create.../)
 *  - Execute: $request->toString();
 *  - Or implicitly execute: $request->asJSON();
 */
class InsightlyRequest{
  /**
   * API URL
   * 
   * @var string
   */
  const URL_BASE = 'https://api.insight.ly';
  
  /**
   * CURL resource
   * 
   * @var resource
   */
  private $curl;
  
  /**
   * URL path outside the base URL
   * 
   * @var string
   */
  private $url_path;
  
  /**
   * Request headers
   * 
   * @var array
   */
  private $headers;
  
  /**
   * Request parameters
   * 
   * @var array
   */
  private $querystrings;
  
  /**
   * Response body
   * 
   * @var string
   */
  private $body;

  /**
   * Request initialisation
   * 
   * @param string $method (GET|DELETE|POST|PUT)
   * @param string $apikey
   * @param string $url_path
   * @throws Exception
   */
  function __construct($method, $apikey, $url_path){
    $this->curl = curl_init();
    $this->url_path = $url_path;
    $this->headers = array("Authorization: Basic " . base64_encode($apikey . ":"));
    $this->querystrings = array();
    $this->body = null;

    switch($method){
    case "GET":
      // GET is the default
      break;
    case "DELETE":
      $this->method("DELETE");
      break;
    case "POST":
      $this->method("POST");
      break;
    case "PUT":
      $this->method("PUT");
      break;
    default: throw new Exception('Invalid HTTP method: ' . $method);
    }

    // Have curl return the response, rather than echoing it
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
  }

  /**
   * Get executed request response
   * 
   * @throws Exception
   * @return string
   */
  public function asString(){
    // This may be useful for debugging
    //curl_setopt($this->curl, CURLOPT_VERBOSE, true);

    $url =  InsightlyRequest::URL_BASE . $this->url_path . $this->buildQueryString();
    curl_setopt($this->curl, CURLOPT_URL, $url);
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);

    $response = curl_exec($this->curl);
    $errno = curl_errno($this->curl);
    if($errno != 0){
      throw new Exception("HTTP Error (" . $errno . "): " . curl_error($this->curl));
    }

    $status_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    if(!($status_code == 200 || $status_code == 201 || $status_code == 202)){
      throw new Exception("Bad HTTP status code: " . $status_code);
    }

    return $response;
  }

  /**
   * Return decoded JSON response
   * 
   * @throws Exception
   * @return mixed
   */
  public function asJSON(){
    $data = json_decode($this->asString());

    $errno = json_last_error();
    if($errno != JSON_ERROR_NONE){
      throw new Exception("Error encountered decoding JSON: " . json_last_error_msg());
    }

    return $data;
  }

  /**
   * Add data to the current request
   * 
   * @param mixed $obj
   * @throws Exception
   * @return InsightlyRequest
   */
  public function body($obj){
    $data = json_encode($obj);

    $errno = json_last_error();
    if($errno != JSON_ERROR_NONE){
      throw new Exception("Error encountered encoding JSON: " . json_last_error_message());
    }

    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
    $this->headers[] = "Content-Type: application/json";
    return $this;
  }

  /**
   * Set request method
   * 
   * @param string $method
   * @return InsightlyRequest
   */
  private function method($method){
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
    return $this;
  }

  /**
   * Add query parameter to the current request
   * 
   * @param string $name
   * @param mixed $value
   * @return InsightlyRequest
   */
  public function queryParam($name, $value){
    // build the query string for this name/value pair
    $querystring = http_build_query(array($name => $value));

    // append it to the list of query strings
    $this->querystrings[] = $querystring;

    return $this;
  }

  /**
   * Build query string for the current request
   * 
   * @return string
   */
  private function buildQueryString(){
    if(count($this->querystrings) == 0){
      return "";
    }
    else{
      $querystring = "?";

      foreach($this->querystrings as $index => $value){
        if($index > 0){
          $querystring .= "&";
        }
        $querystring .= $value;
      }

      return $querystring;
    }
  }
}
