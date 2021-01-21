<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class holdedclient
{
    public $apikey = '';
    public function __construct($key) {
	    $this->apikey = $key;
	}

    #Contacts
    public function getcontacts() {
    	return $this->get('/get/contacts',null);
    }
    public function findcontact($extra) {
        return $this->get('/find/contact',$extra);
    }
    public function createcontact($extra){
        return $this->get('/add/contact',$extra);
    }
    public function updatecontact($cid,$extra){
        return $this->get('/update/contact/'.$cid,$extra);
    }
    public function deletecontact($cid){
        return $this->get('/delete/contact/'.$cid,[]);
    }

    #Products
    public function getproducts() {
        return $this->get('/get/products',null);
    }
    public function findproduct($extra) {
        return $this->get('/find/product',$extra);
    }
    public function createproduct($extra){
        return $this->get('/add/product',$extra);
    }
    public function updateproduct($pid,$extra){
        return $this->get('/update/product/'.$pid,$extra);
    }
    public function updatestock($pid,$units){
        return $this->get('/update/product/stock/'.$pid,['units' => $units]);
    }
    public function deleteproduct($pid){
        return $this->get('/delete/product/'.$pid,[]);
    }

    #Invoices
    public function createinvoice($extra){
        if(array_key_exists('items', $extra)){
            $extra['items'] = json_encode($extra['items']);
        }
        return $this->get('/add/doc/invoice',$extra);
    }
    public function getinvoices($extra=null) {
        return $this->get('/get/doc/invoice',null);
    }
    public function findinvoice($extra) {
        return $this->get('/find/doc/invoice',$extra);
    }
    public function updateinvoice($docid,$extra){
        return $this->get('/update/doc/invoice/'.$docid,$extra);
    }
    public function deleteinvoice($docid){
        return $this->get('/delete/doc/invoice/'.$docid,[]);
    }
    public function payinvoice($docid,$extra){
        return $this->get('/pay/invoice/'.$docid,$extra);
    }


    #Salesreceipts
    public function createsalesreceipt($extra){
        if(array_key_exists('items', $extra)){
            $extra['items'] = json_encode($extra['items']);
        }
        return $this->get('/add/doc/salesreceipt',$extra);
    }
    public function getsalesreceipts() {
        return $this->get('/get/doc/salesreceipt',null);
    }
    public function findsalesreceipt($extra) {
        return $this->get('/find/doc/salesreceipt',$extra);
    }
    public function updatesalesreceipt($docid,$extra){
        return $this->get('/update/doc/salesreceipt/'.$docid,$extra);
    }
    public function deletesalesreceipt($docid){
        return $this->get('/delete/doc/salesreceipt/'.$docid,[]);
    }
    public function paysalesreceipt($docid,$extra){
        return $this->get('/pay/salesreceipt/'.$docid,$extra);
    }



    #Creditnotes
    public function createcreditnote($extra){
        if(array_key_exists('items', $extra)){
            $extra['items'] = json_encode($extra['items']);
        }
        return $this->get('/add/doc/creditnote',$extra);
    }
    public function getcreditnotes() {
        return $this->get('/get/doc/creditnote',null);
    }
    public function findcreditnote($extra) {
        return $this->get('/find/doc/creditnote',$extra);
    }
    public function updatecreditnote($docid,$extra){
        return $this->get('/update/doc/creditnote/'.$docid,$extra);
    }
    public function deletecreditnote($docid){
        return $this->get('/delete/doc/creditnote/'.$docid,[]);
    }

    #Salesorders
    public function createsalesorder($extra){
        if(array_key_exists('items', $extra)){
            $extra['items'] = json_encode($extra['items']);
        }
        return $this->get('/add/doc/salesorder',$extra);
    }
    public function getsalesorders() {
        return $this->get('/get/doc/salesorder',null);
    }
    public function findsalesorder($extra) {
        return $this->get('/find/doc/salesorder',$extra);
    }
    public function updatesalesorder($docid,$extra){
        return $this->get('/update/doc/salesorder/'.$docid,$extra);
    }
    public function deletesalesorder($docid){
        return $this->get('/delete/doc/salesorder/'.$docid,[]);
    }

    #Sending doc
    public function senddoc($doctype,$docid,$extra){
        return $this->get('/send/'.$doctype.'/'.$docid,$extra);
    }

    #Get pdf
    public function getpdf($doctype,$docid){
        return $this->get('/get/pdf/'.$doctype,['id' => $docid]);
    }



    #Accounting moves
    public function addentry($fields) {
        return $this->get('/add/entry',$fields);
    }
    public function addentries($entries) {
        $records = [];
        $records['entries'] = json_encode($entries);
        return $this->get('/add/entries',$records);
    }
    public function getentry($entryid) {
        return $this->get('/get/entry',['entryid' => $entryid]);
    }
    public function deleteentry($entryid) {
        return $this->get('/delete/entry',['entryid' => $entryid]);
    }

    #Numbering series
    public function getnumbering($type) {
        return $this->get('/get/numbering/'.$type,null);
    }
    public function createnumbering($type,$extra){
        return $this->get('/add/numbering/'.$type,$extra);
    }
    public function updatenumbering($type,$nlid,$extra){
        return $this->get('/update/numbering/'.$type.'/'.$nlid,$extra);
    }
    public function deletenumbering($type,$nlid){
        return $this->get('/delete/numbering/'.$type.'/'.$nlid,[]);
    }

    #Sales channels
    public function getsaleschannels() {
        return $this->get('/get/saleschannels',null);
    }
    public function createsaleschannel($extra){
        return $this->get('/add/saleschannel',$extra);
    }
    public function updatesaleschannel($scid,$extra){
        return $this->get('/update/saleschannel/'.$scid,$extra);
    }
    public function deletesaleschannel($scid){
        return $this->get('/delete/saleschannel/'.$scid,[]);
    }


    #Attach file
    public function attachfiledoc($doctype,$docid,$path,$setmain){
        $file_name_with_full_path = new CURLFile(realpath($path));
        $post = array('key'=>$this->apikey, 'setmain' => intval($setmain), 'extra_info' => 'attachment','file'=>$file_name_with_full_path);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,'https://app.holded.com/api/v1/attachfile/'.$doctype.'/'.$docid);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        return json_decode(curl_exec($ch),true);
    }


    #CRM
    public function crmaddlead($extra){
        return $this->get('/add/crm/lead',$extra);
    }

    ####################

    public function get($url,$extra){
    	$params=array('key'=>$this->apikey);
        $url = 'https://app.holded.com/api/v1'.$url;
    	if($extra){ foreach($extra as $k=>$v){ $params[$k] = $v; }}
    	$adb_handle = curl_init();
		$options = array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => $url,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $params,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_VERBOSE => true
		); 
		curl_setopt_array($adb_handle,$options);
		return json_decode(curl_exec($adb_handle),true);
    }
}

?>