<?php if (!defined('APPLICATION')) exit();

$PluginInfo['NotifyCommentOnCommented'] = array(
   'Name' => 'Notify Comment on Commented',
   'Description' => 'Notifies if someone has commented on a disicussion the user has commented on',
   'Version' => '0.1.1b',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'Author' => "Paul Thomas",
   'AuthorEmail' => 'dt01pq_pt@yahoo.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/x00',
   'MobileFriendy' => TRUE
);

class NotifyCommentOnCommented extends Gdn_Plugin {
	public function ProfileController_AfterPreferencesDefined_Handler($Sender){
		$Notifications = &$Sender->Preferences['Notifications'];
		$Notifications['Email.CommentOnCommented'] =
			T('Notify me when people reply to discussions I\'ve commented on.');
		$Notifications['Popup.CommentOnCommented'] =
			T('Notify me when people reply to discussions I\'ve commented on.');
	}
	
	public function CommentModel_BeforeNotification_Handler($Sender, &$Args){
		$Comment = &$Args['Comment'];
		$Session = Gdn::Session();
			
		$ActivityModel = &$Args['ActivityModel'];
		$Discussion = &$Args['Discussion'];
		$DiscussionID = GetValue('DiscussionID', $Discussion);
		$CommentID = GetValue('CommentID', $Comment);
		
		$NotifiedUsers = &$Args['NotifiedUsers'];
		$UserModel = Gdn::UserModel();
		
		$Story = '['.$Discussion->Name."]\n".GetValue('Body', $Comment, '');
		
		
		$UsersInDiscussion = $Sender->SQL
         ->Select('DISTINCT(c.InsertUserID) as InsertUserID')
         ->From('Comment c')
         ->Where('c.DiscussionID', $DiscussionID)
         ->Get();
         
         
        foreach ($UsersInDiscussion->Result() as $Participant) {
            if (in_array($Participant->InsertUserID, $NotifiedUsers) || $Participant->InsertUserID == $Session->UserID)
               continue;

            $UserMayView = $UserModel->GetCategoryViewPermission($Participant->InsertUserID, $Discussion->CategoryID);

            if ($UserMayView) {

               $NotifiedUsers[] = $Participant->InsertUserID;

               $ActivityID = $ActivityModel->Add(
                  $Session->UserID,
                  'CommentOnCommented',
                  Anchor(Gdn_Format::Text($Discussion->Name), 'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID),
                  $Participant->InsertUserID,
                  '',
                  'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID,
                  FALSE
               );
               
               $ActivityModel->QueueNotification($ActivityID, $Story);
            }
         }
	}
	
	public function Base_BeforeDispatch_Handler($Sender){
		if(C('Plugins.'.$this->GetPluginIndex().'.Version')!=$this->PluginInfo['Version'])
			$this->Setup();
	}
	
	public function Setup(){
		if (Gdn::SQL()->GetWhere('ActivityType', array('Name' => 'CommentOnCommented'))->NumRows() == 0)
				Gdn::SQL()->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'CommentOnCommented', 'FullHeadline' => '%1$s commented on a %8$s you commented on.', 'ProfileHeadline' => '%1$s commented on a %8$s you commented on.', 'RouteCode' => 'discussion', 'Notify' => '1', 'Public' => '0'));
		
		SaveToConfig('Plugins.'.$this->GetPluginIndex().'.Version',$this->PluginInfo['Version']);
	
	}
	
}
