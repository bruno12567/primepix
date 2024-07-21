<?php
class Account {

    private $con;
    private $errors = array();

    public function __construct($con) {
        $this->con = $con;
    }

    public function updateDetails($firstName, $lastName, $email, $username) {
        $this->validateFirstName($firstName);
        $this->validateLastName($lastName);
        $this->validateEmail($email, $username);

        if(empty($this->errors)) {
            $query = $this->con->prepare("UPDATE users SET firstName=:firstName, lastName=:lastName, email=:email WHERE username=:username");
            $query->bindValue(":firstName", $firstName);
            $query->bindValue(":lastName", $lastName);
            $query->bindValue(":email", $email);
            $query->bindValue(":username", $username);

            return $query->execute();
        }

        return false;
    }

    public function updatePassword($oldPassword, $newPassword, $newPassword2, $username) {
        $this->validateOldPassword($oldPassword, $username);
        $this->validateNewPasswords($newPassword, $newPassword2);

        if(empty($this->errors)) {
            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);

            $query = $this->con->prepare("UPDATE users SET password=:password WHERE username=:username");
            $query->bindValue(":password", $newPasswordHash);
            $query->bindValue(":username", $username);

            return $query->execute();
        }

        return false;
    }

    public function getFirstError() {
        if(!empty($this->errors)) {
            return $this->errors[0];
        }
        return "";
    }

    private function validateFirstName($firstName) {
        if(strlen($firstName) < 2 || strlen($firstName) > 25) {
            array_push($this->errors, "Your first name must be between 2 and 25 characters");
        }
    }

    private function validateLastName($lastName) {
        if(strlen($lastName) < 2 || strlen($lastName) > 25) {
            array_push($this->errors, "Your last name must be between 2 and 25 characters");
        }
    }

    private function validateEmail($email, $username) {
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            array_push($this->errors, "Invalid email format");
            return;
        }

        $query = $this->con->prepare("SELECT email FROM users WHERE email=:email AND username != :username");
        $query->bindValue(":email", $email);
        $query->bindValue(":username", $username);
        $query->execute();

        if($query->rowCount() != 0) {
            array_push($this->errors, "Email is already in use");
        }
    }

    private function validateOldPassword($oldPassword, $username) {
        $query = $this->con->prepare("SELECT password FROM users WHERE username=:username");
        $query->bindValue(":username", $username);
        $query->execute();

        $row = $query->fetch(PDO::FETCH_ASSOC);
        $currentPassword = $row["password"];

        if(!password_verify($oldPassword, $currentPassword)) {
            array_push($this->errors, "Your old password is incorrect");
        }
    }

    private function validateNewPasswords($newPassword, $newPassword2) {
        if($newPassword != $newPassword2) {
            array_push($this->errors, "Your new passwords do not match");
            return;
        }

        if(strlen($newPassword) < 5 || strlen($newPassword) > 30) {
            array_push($this->errors, "Your password must be between 5 and 30 characters");
        }
    }
}
?>
