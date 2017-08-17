<?php

namespace SocialSignIn\ClientSuccessIntegration\Person;

use Assert\Assertion;

final class UserRepository implements RepositoryInterface
{
    private $token;

    private $api = "https://api.clientsuccess.com/v1";

    private $username = "";

    private $password = "";

    private $employees = [];

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

    }

    protected function login()
    {

        if (!empty($this->token)) {
            return;
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api . "/auth");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "username=" . $this->username . "&password=" . $this->password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/x-www-form-urlencoded"
        ));

        $response = curl_exec($ch);
        curl_close($ch);
        Assertion::isJsonString($response, "Request to client success failed (non-json returned; can't get token)");

        $data = json_decode($response);
        $this->token = $data->access_token;


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api . "/employees");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: " . $this->token,
            "Content-Type: application/json"
        ));

        $response = curl_exec($ch);
        curl_close($ch);
        $this->employees = json_decode($response);
    }

    private function getCurrentEmployee($email)
    {
        $this->login();
        foreach ($this->employees as $employee) {
            if (strtolower($employee->email) == strtolower($email)) {
                return $employee;
            }
        }

        return $this->employees[0];
    }

    /**
     * @param string $query
     *
     * @return Entity[]
     */
    public function search($query)
    {

        $this->login();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api . "/contacts/search?term='" . $query . "'");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: " . $this->token,
            "Content-Type: application/json"
        ));

        $clients = json_decode(curl_exec($ch));
        curl_close($ch);

        $persons = [];
        foreach ($clients as $contact) {
            $persons[] = new Entity($contact->clientId . ":" . $contact->id,
                $contact->firstName . " " . $contact->lastName);
        }

        return $persons;
    }

    public function addToDo($id, $name)
    {

        $this->login();

        $clientId = explode(":", $id)[0];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api . "/clients/$clientId/to-dos");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'name' => $name
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: " . $this->token,
            "Content-Type: application/json"
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        var_dump($response);
        die(); // @todo
    }


    public function addNote($clientId, $contactID, $subject, $note)
    {

        $this->login();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api . "/clients/$clientId/interactions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: " . $this->token
        ));
        $params = [
            'createdByEmployeeId' => $this->getCurrentEmployee("")->id,
            'interactionTypeId' => 1,
            'subject' => $subject,
            'clientId' => $clientId,
            'contactID' => $contactID,
            'note' => $note
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        curl_exec($ch);
        curl_close($ch);

        return json_encode(['success' => true]);

    }

    /**
     * @param string $id
     *
     * @return Entity|null
     */
    public function get($id)
    {

        $this->login();
        
        $clientId = explode(":", $id)[0];
        $contactId = explode(":", $id)[1];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api . "/clients/" . $clientId . "/contacts/" . $contactId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: " . $this->token
        ));

        $contactResponse = curl_exec($ch);
        curl_close($ch);


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api . "/clients/" . $clientId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: " . $this->token
        ));

        $clientResponse = curl_exec($ch);
        curl_close($ch);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api . "/clients/" . $clientId . "/interactions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: " . $this->token
        ));
        $interactionResponse = curl_exec($ch);
        curl_close($ch);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api . "/clients/" . $clientId . "/to-dos");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: " . $this->token
        ));
        $todosResponse = curl_exec($ch);
        curl_close($ch);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->api . "/subscriptions?clientId=" . $clientId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: " . $this->token
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
