<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class SendGridAPI extends Controller
{
  /**
  @author Duncan Pierce <duncan@duncanpierce.com>
  @purpose Gets the recipient list which is on your contactdb, returns the lists which are available to be added to.
  */
  public static function getRecipientList($client){
    $res = $client->request('GET', 'contactdb/recipients', [
      'headers' => [
        'Authorization' => env("SENDGRID_API_KEY"),
      ]
    ]);



    $reformat = json_decode($res->getBody(), true);
    return $reformat;
  }
  /**
  @author Duncan Pierce <duncan@duncanpierce.com>
  @purpose Search SendGrids DB on their $.
  */
  public static function searchRecipients($client, $userEmail){
    $res = $client->request('GET', 'contactdb/recipients/search?email=' .  $userEmail, [
        'headers' => [
          'Authorization' => env("SENDGRID_API_KEY"),
        ]
      ]
    );
    $reformat = json_decode($res->getBody(), true);
    if($reformat['recipient_count'] != 0){
      return $reformat;
    }else{
      return false;
    }
  }
  /**
  @author Duncan Pierce <duncan@duncanpierce.com>
  @purpose Get a recipients info.
  */
  public static function getRecipientFromEmail($client, $emailValue){
    $res = $client->request('GET', 'contactdb/recipients/search?email=' .  $emailValue, [
        'headers' => [
          'Authorization' => env("SENDGRID_API_KEY"),
        ]
      ]
    );
    $reformat = json_decode($res->getBody(), true);
    return $reformat;
  }
  /**
  @author Duncan Pierce <duncan@duncanpierce.com>
  @purpose Gets the list of contacts you have in your sendgrid api key
  */
  public static function getContactList($client){
    $res = $client->request('GET', 'contactdb/lists', [
      'headers' => [
        'Authorization' => env("SENDGRID_API_KEY"),
      ]
    ]);



    if($res->getStatusCode() != "200"){
      return false;
    }
    $reformat = json_decode($res->getBody(), true);
    $lists = $reformat['lists'];
    return $lists;

  }

  /**
  @author Duncan Pierce <duncan@duncanpierce.com>
  @purpose Adds the recipient to your contacts
  @params HTTPClient, Sendgrid List, and their email
  */
  public static function createRecipient($client, $list, $userEmail, $customFields){
    $body['email'] = $userEmail;
    $body['ipaddress'] = \Request::ip();
    $keys = ['your', 'custom', 'fields', 'go', 'here'];
    foreach($keys as $key){
      if(isset($customFields[$key])){
        $body[$key] = $customFields[$key];
      }
    }
    $res = $client->request('POST', 'contactdb/recipients', [
      'headers' => [
        'Authorization' => env("SENDGRID_API_KEY"),
      ],
      'json' => [$body],
    ]);
    if($res->getStatusCode() != "201"){

      //Log here if you wish (database or api or whatever)
      return false;
    }
    $reformat = json_decode($res->getBody(), true);
    return $reformat;
  }

  /**
    @author Duncan Pierce <duncan@duncanpierce.com>
    @description: if you already have a recipient id, you can go ahead and directly add them to a list ID
  */
  public static function addToListWithID($client, $recid, $listid){
    $res = $client->request('POST', 'contactdb/lists/' . $listid . '/recipients/' . $recid,
    [
      'headers' => [
        'Authorization' => env("SENDGRID_API_KEY")
      ]
    ]);

    if($res->getStatusCode() != "201"){

      return false;
    }
    return true;
  }
  /**
    @author Duncan Pierce <duncan@duncanpierce.com>
    @description: if you already have a recipient id, you can remove them from a list id
  */
  public static function removeFromListWithID($client, $recid, $listid){
    $res = $client->delete('contactdb/lists/' . $listid . '/recipients/' . $recid,
    [
      'headers'=> [
        'Authorization' => env("SENDGRID_API_KEY")
      ]
    ]);

    if($res->getStatusCode() != "204"){

      return false;
    }
    return true;
  }

  /**
    @author: Duncan Pierce <duncan@duncanpierce.com>
    @description: Searches for the lists which a recipient ID is on.
  */
  public static function searchListByRecID($client, $recipientID){
    $res = $client->request('GET', 'contactdb/recipients/' . $recipientID . '/lists',
    [
      'headers'=> [
        'Authorization' => env("SENDGRID_API_KEY")
      ]
    ]);

    $reformat = json_decode($res->getBody(), true);
    return $reformat;
  }

  /**
  @author Duncan Pierce <duncan@duncanpierce.com>
  @description Sends an email which is a template pulled from
  the HTML Controller - this has had its apostraphes
  Stripped from it, so be careful with that. It needs to be json encoded
  instead to strip the slashes safely.
  If the email could not be sent, it will //Log here if you wish (database or api or whatever)
  */




  public static function sendEmail($client, $userEmail, $recId, $case, $subject){
    switch ($case) {
        case 1:
            $doc = 'Your Email Document for Case 1';
            break;
        case 2:
            $doc = 'Your Email Document for Case 2';
            break;
        case 3:
            $doc = 'Your Email Document for Case 3';
            break;
        default:
            return false;
    }
    $clientFrom = "your@email.address";

    $body = ['personalizations' => [array('to' =>
     [array('email' => $userEmail,
      'name' => 'Test')], 'subject' => $subject)],
       'from' => ['email' => $clientFrom, 'name' => 'yourmail'],
        'reply_to' => ['email' => $clientFrom, 'name' => 'yourmail Customer Service'],
         'content' => array(['type' => 'text/html', 'value' => $doc])];
    if($case == 1 || $case == 3){
      $body['ip_pool_name'] = 'welcome';
    }
    if($case == 2){
      $body['ip_pool_name'] = 'delivery';
    }
    $res = $client->request('POST', 'mail/send', [
        'headers' => [
          'Authorization' => env("SENDGRID_API_KEY"),
        ],
        'json' => $body,
      ]
    );



    if($res->getStatusCode() != "202"){
      $this->deleteRecipientForResign($client, $recId, $userEmail, 'Failed to send onboard email');
      return false;
    }

    return true;
  }
  /**
    @author Duncan Pierce <duncan@duncanpierce.com>
    @description Deletes the users recipient id from our list so that we can have them sign up once again.
    In order to save the information of this we //Log here if you wish (database or api or whatever)
    as well as the reason of the failure.
    @params Client, Recipient Id, Email of the User, Reason for failure
    @return mixed
  */
  private function deleteRecipientForResign($client, $recId, $userEmail, $reason){
    $bademail = $userEmail;
    $recipient = $recId['persisted_recipients'][0];



    //Log here if you wish (database or api or whatever)


    $res = $client->delete('contactdb/recipients/' . $recipient, [
      'headers' => [
        'Authorization' => env("SENDGRID_API_KEY"),
      ]
    ]);
    $reformat = json_decode($res->getBody(), true);

    return false;
  }

}
