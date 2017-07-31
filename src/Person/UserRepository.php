<?php

namespace SocialSignIn\ExampleCrmIntegration\Person;

final class UserRepository implements RepositoryInterface
{
    private $token;
    
    private $api = "https://api.clientsuccess.com/v1";
    
    private $username = "";
    
    private $password = "";
    
    public function __construct($username, $password)
    {
      
        $this->username = $username;
        $this->password = $password;
        
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api."/auth");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "username=".$this->username."&password=".$this->password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/x-www-form-urlencoded"
        ));

        $response = curl_exec($ch);
        curl_close($ch);
        $this->token = json_decode($response)->access_token;
    }
    
    private function getAllClients() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api."/clients");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "Accept: application/json",
          "Authorization: ".$this->token
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }
    
    private function getAllContactsForClient ($clientId) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api."/clients/".$clientId."/contacts");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "Accept: application/json",
          "Authorization: ".$this->token
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }
    
    /**
     * @param string $query
     *
     * @return Entity[]
     */
    public function search($query)
    {
      
        $persons = [];
        foreach ($this->getAllClients() as $client) {
          $contacts = $this->getAllContactsForClient($client->id);
          
          if (count($contacts) === 0) {
            continue;
          }

          foreach($contacts as $contact) {
            $persons[] = new Entity($client->id . ":" . $contact->id, $contact->firstName . " " . $contact->lastName);
          }
        }
      
        $query = preg_quote($query, '!');

        $matched = [];

        foreach ($persons as $person) {
            if (preg_match('!' . $query . '!i', $person->getName())) {
                $matched[] = $person;
            }
        }

        return $matched;
    }
    
    public function addToDo ($id, $name) {
      $clientId = explode(":", $id)[0];
      
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $this->api."/clients/$clientId/to-dos");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
          'name' => $name
      ]));
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: ".$this->token,
        "Content-Type: application/json"
      ));

      $response = curl_exec($ch);
      curl_close($ch);

      var_dump($response);
      die();
    }

    /**
     * @param string $id
     *
     * @return Entity|null
     */
    public function get($id)
    {
      
        $clientId = explode(":", $id)[0];
        $contactId = explode(":", $id)[1];
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $this->api."/clients/".$clientId."/contacts/".$contactId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "Accept: application/json",
          "Authorization: ".$this->token
        ));
        
        $contactResponse = curl_exec($ch);
        curl_close($ch);
        
        
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api."/clients/".$clientId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "Accept: application/json",
          "Authorization: ".$this->token
        ));
        
        $clientResponse = curl_exec($ch);
        curl_close($ch);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api."/clients/".$clientId."/interactions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "Accept: application/json",
          "Authorization: ".$this->token
        ));
        $interactionResponse = curl_exec($ch);
        curl_close($ch);
        
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api."/clients/".$clientId."/to-dos");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "Accept: application/json",
          "Authorization: ".$this->token
        ));
        $todosResponse = curl_exec($ch);
        curl_close($ch);
        
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api."/subscriptions?clientId=".$clientId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "Accept: application/json",
          "Authorization: ".$this->token
        ));
        $subscriptionResponse = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($contactResponse);
        $data->client = json_decode($clientResponse);
        $data->interactions = json_decode($interactionResponse);
        $data->subscriptions = json_decode($subscriptionResponse);
        $data->todos = json_decode($todosResponse);
        
        return $data;
    }
}
