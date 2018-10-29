<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class SendMail extends Controller{
  /**
    @author Duncan Pierce <duncan@duncanpierce.com>
  */
  public function SendMail($userInput){

    // Adds a guzzle client to be passed down - base of API.sendgrid
    $client = new Client(['base_uri' => 'https://api.sendgrid.com/v3/']);

    // Gets the contact lists (The list you want to add to goes in the .env file
    // or as a variable)
    $listInformation = $this->getContactList($client);

    // Checks to see if the email input is already on file in your Sendgrid Account
    $search = $this->searchRecipients($client, $userInput);

    //Return false if the search comes back true
    if($search == true){
      return false;
    }

    // Adds the email as a Recipient (Contact)
    $recipient = $this->addRecipient($client, $listInformation, $userInput);

    // Adds the Contact ID to your recipient list of choice
    $listReturn = $this->addRecipientToList($client, $listInformation, $recipient, $userInput);

    $this->getRecipientList($client);

    return true;
  }

  /**
  @author Duncan Pierce <duncan@duncanpierce.com>
  @purpose Gets the recipient list which is on your contactdb, returns the lists which are available to be added to.
  */
  protected function getRecipientList($client){
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
  @purpose Gets the list of contacts you have in your sendgrid api key
  */
  protected function getContactList($client){
    $res = $client->request('GET', 'contactdb/lists', [
      'headers' => [
        'Authorization' => env("SENDGRID_API_KEY"),
      ]
    ]);
    $reformat = json_decode($res->getBody(), true);
    $lists = $reformat['lists'];
    return $lists;

  }
  /**
  @author Duncan Pierce <duncan@duncanpierce.com>
  @purpose Search SendGrids DB on their $.
  */
  protected function searchRecipients($client, $emailSent){
    $res = $client->request('GET', 'contactdb/recipients/search?email=' .  $emailSent['email'], [
        'headers' => [
          'Authorization' => env("SENDGRID_API_KEY"),
        ]
      ]
    );
    $reformat = json_decode($res->getBody(), true);
    if($reformat['recipient_count'] != 0){
      return true;
    }else{
      return false;
    }
  }

  /**
  @author Duncan Pierce <duncan@duncanpierce.com>
  @purpose A local way of checking your contact list - Heavy on the server, I
  recc you use SendGrids server to do it.
  */
  protected function checkContactList($client, $list, $emailSent){
    $res = $client->request('GET', 'contactdb/lists/' . $list[0]['id'] . '/recipients', [
      'headers' => [
        'Authorization' => env("SENDGRID_API_KEY"),
      ]
    ]);
    $returnFormat = json_decode($res->getBody(), true);
    foreach($returnFormat['recipients'] as $emails){
      if($emails['email'] == $emailSent['email']){
        $returnFormat = false;
        break;
      }
    }
    return $returnFormat;
  }
  /**
  @author Duncan Pierce <duncan@duncanpierce.com>
  @purpose Adds the recipient to your contacts
  */
  protected function addRecipient($client, $list, $emailSent){
    // You can add other JSON requests - just make sure that you update your form input as needed.
      $res = $client->request('POST', 'contactdb/recipients', [
        'headers' => [
          'Authorization' => env("SENDGRID_API_KEY"),
        ],
        'json' => [
          [
              'email' => $emailSent['email']
          ]
        ],
      ]);
    $reformat = json_decode($res->getBody(), true);
    return $reformat;
  }
  /**
  @author Duncan Pierce <duncan@duncanpierce.com>
  @purpose Adds the recipient id to the list which is defined above
  */
  protected function addRecipientToList($client, $listInformation, $recipientInformation, $emailSent){
    foreach($listInformation as $list){
      if($list['name'] == env("SENDGRID_LIST_NAME")){
        $listid = $list['id'];
        break;
      }
    }
    // Supports multiple recipients.
    foreach($recipientInformation['persisted_recipients'] as $persRecip){
      $res = $client->request('POST', 'contactdb/lists/' . $listid . '/recipients/' . $persRecip,
      [
        'headers' => [
          'Authorization' => env("SENDGRID_API_KEY")
        ]
      ]);
      $reformat = json_decode($res->getBody(), true);
      return $reformat;
    }
  }
}
