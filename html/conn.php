<?php
try {
    // Connexion a la Base de Donnée
    $conn = new PDO("mysql:host=<local_ip>;dbname=<db>", "<user>", "<password>");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Préparation de l'INSERT
    $stmt = $conn->prepare("INSERT INTO users (user_name, user_password, user_mail) VALUES (?, ?, ?)");
    $stmt->bindParam(1, $name);
    $stmt->bindParam(2, $encrypted);
    $stmt->bindParam(3, $mail);
    // Déclaration des Varriables pour l'INSERT
    $name = $_POST['name'];
    // Hash du Password avec un Salt
    $options = [
        'cost' => 11
    ];
    $hash = password_hash($_POST['password'], PASSWORD_BCRYPT, $options);
    // Double Encryption w/ HMAC SHA512 by a Secret Key
    $encrypted = hash_hmac('sha512', $hash, 'ThE.SeCrEt.Is.ThE.KeY');
    $mail = $_POST['mail'];
    // Execution de la requete
    $stmt->execute();
    $stmt = null;
    $conn = null;
} catch(PDOException $err) {
  echo "ERROR: Unable to connect: " . $err->getMessage();
  die();
}
?>
