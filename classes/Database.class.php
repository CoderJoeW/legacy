<?php

require_once('/var/www/hackerexperience/config.php');

use HE\Database\PDO\Model;

class LRSys {

    public $name;
    public $user;
    private $pass;
    public $email;
    public $keepalive;
    
    private $session;
    private $lang;
    private $pdo;
    private $process;

    private $log;
    private $ranking;
    private $storyline;
    private $clan;
    
    function __construct() {

        $this->pdo = PDO_DB::factory();
        
        require_once 'Session.class.php';

        $this->session = new Session();


        require 'Player.class.php';
        require 'PC.class.php';
        require 'Ranking.class.php';
        require 'Storyline.class.php';
        require 'Clan.class.php';

        $this->log = new LogVPC();
        $this->ranking = new Ranking();
        $this->storyline = new Storyline();
        $this->clan = new Clan();

        $this->keepalive = FALSE;
        
    }

    public function set_keepalive($keep){
        $this->keepalive = $keep;
    }

    public function register($regUser, $regPass, $regMail) {
        $db = Model::getInstance('localhost');

        $this->user = $regUser;
        $this->pass = $regPass;
        $this->email = $regMail;

        $params = [
            'ip' => $_SERVER['REMOTE_ADDR']
        ];

        $spamCheck = $db->select('SELECT Count(*) AS total FROM stats_register WHERE ip=:ip AND TIMESTAMPDIFF(MINUTE, registrationDate, NOW()) < 10', $params)[0];

        if($spamCheck->total >= 1){
            exit('IP blocked for multiple registrations. Try again in 10 minutes.');
        }

        if ($this->verifyRegister()) {

            require 'BCrypt.class.php';
            $bcrypt = new BCrypt();
            
            $hash = $bcrypt->hash(htmlentities($this->pass));
            
            $gameIP1 = rand(0, 255);
            $gameIP2 = rand(0, 255);
            $gameIP3 = rand(0, 255);
            $gameIP4 = rand(0, 255);

            $gameIP = $gameIP1 . '.' . $gameIP2 . '.' . $gameIP3 . '.' . $gameIP4;

            $params = [
                'login' => $this->user,
                'password' => $hash,
                'gamePass' => 'somerandomgeneratedpass', //TODO
                'email' => $this->email,
                'gameIP' => $gameIP
            ];

            $userId = $db->insert("INSERT INTO users SET {$db->createParameterString($params)}", $params);

            $params = [ 'uid' => $userId, 'dateJoined' => time()];
            $db->insert("INSERT INTO users_stats {$db->createParameterString($params)}", $params);

            $params = ['userID' => $userId, 'name' => 'Server #1'];
            $db->insert("INSERT INTO hardware {$db->createParameterString($params)}", $params);

            $params = ['userID' => $userId, 'text' => 'bruh idek what the author intended here'];
            $db->insert("INSERT INTO log {$db->createParameterString($params)}", $params);

            $params = ['userID' => $userId];
            $db->insert("INSERT INTO cache {$db->createParameterString($params)}", $params);

            $params = ['userID' => $userId, 'expireCache' => time()];
            $db->insert("INSERT INTO cache_profile {$db->createParameterString($params)}", $params);

            $params = ['userID' => $userId];
            $db->insert("INSERT INTO hist_users_current {$db->createParameterString($params)}", $params);

            $params = ['userID' => $userId, 'rank' => '-1'];
            $db->insert("INSERT INTO ranking_user {$db->createParameterString($params)}", $params);

            $params = ['userID' => $userId];
            $db->insert("INSERT INTO certifications {$db->createParameterString($params)}", $params);

            $params = ['userID' => $userId];
            $db->insert("INSERT INTO users_puzzle {$db->createParameterString($params)}", $params);

            $params = ['userID' => $userId];
            $db->insert("INSERT INTO users_learning {$db->createParameterString($params)}", $params);

            $params = ['userID' => $userId];
            $db->insert("INSERT INTO users_language {$db->createParameterString($params)}", $params);

            $params = [
                'user' => $this->user
            ];

            $regInfo = $db->select('SELECT Count(*) AS total, id FROM users WHERE login=:user LIMIT 1', $params)[0];
            
            if($regInfo->total == 0){
                $this->session->addMsg('Error while completing registration. Please, try again later.', 'error');
                return FALSE;
            }

            require 'EmailVerification.class.php';
            $EmailVerification = new EmailVerification();
            
            if(!$EmailVerification->sendMail($regInfo->id, $this->email, $this->user)){
                $this->session->addMsg('Registration complete. You can login now.', 'notice');
                //return FALSE;
                //TODO: report to admin
            }
            
            require 'Finances.class.php';
            $finances = new Finances();
            
            $finances->createAccount($regInfo->id);

            $params = [
                'userID' => $regInfo->id,
                'ip' => $_SERVER['REMOTE_ADDR']
            ];

            $db->insert("INSERT INTO stats_register SET {$db->createParameterString($params)}", $params);

            $this->session->addMsg('Registration complete. You can login now.', 'notice');

            return TRUE;

        } else {

            return FALSE;
        }
    }

    private function verifyRegister() {
        $db = Model::getInstance('localhost');

        $system = new System();
        
        if(!$system->validate($this->user, 'username')){
            $this->session->addMsg(sprintf(_('Invalid username. Allowed characters are %s.'), '<strong>azAZ09._-</strong>'), 'error');
            return FALSE;
        }
        
        if(!$system->validate($this->email, 'email')){
            $this->session->addMsg(sprintf(_('The email %s is not valid.'), '<strong>'.$this->email.'</strong>'), 'error');
            return FALSE;
        }

        //pegando spam dos fdp: rIcFCzREv2VOPIU@rIcFCzREv2VOPIU.com 
        //76437363@gmail.com
        //UdJ@jgD.com
        if ((strlen(preg_replace('![^A-Z]+!', '', $this->email)) >= 5 && preg_match_all("/[0-9]/", $this->email) >= 2) || preg_match_all("/[0-9]/", $this->email) >= 5){
            $this->session->addMsg(_('Registration complete. You can login now.'), 'notice');
            return FALSE;
        }

        if (strlen(preg_replace('![^A-Z]+!', '', $this->email)) >= 2 && strlen($this->email) <= 12){
            $this->session->addMsg(_('Registration complete. You can login now.'), 'notice');
            return FALSE;
        }
        
        
        
        //I check in the bank if there is already a registered user or email.
        $this->session->newQuery();

        $params = [
            'login' => $this->user,
            'email' => $this->email
        ];

        $results = $db->select('SELECT email FROM users WHERE login=:login OR email=:email LIMIT 1', $params);

        if (count($results) > 0) {

            if ($results[0]->email == $this->email) {
                $this->session->addMsg('This email is already used.', 'error');
            } else {
                $this->session->addMsg('This username is already taken.', 'error');
            }
            
            return FALSE;
            
            // 2019: what could possibly go wrong?
            //ainda falta verificar se email tá ok, se tem algum caracter especial, sql inject etc etc, mas fica pra depois                       
        } elseif (strlen($this->user) == '0' || strlen($this->pass) == '0' || strlen($this->email) == '0') {

            $this->session->addMsg('Some fields are empty.', 'error');
            
            return FALSE;
        }
        
        if(strlen($this->user) > 15){
            $this->session->addMsg('Yor username is too big :( Please, limit it to 15 characteres.', 'error');
            
            return FALSE;
        }
        
        return TRUE;
        
    }

    public function login($logUser, $logPass, $special = FALSE) {
                
        date_default_timezone_set('UTC');
        
        $facebook = $twitter = $remember = FALSE;
        
        if($special){
            if($special == 'remember'){
                $remember = TRUE;
            } elseif($special == 'facebook'){
                $facebook = TRUE;
            } elseif($special == 'twitter') {
                $twitter = TRUE;
                unset($_SESSION['twitter_data']);
            } else {
                exit("Edit special");
            }
        }
          
        if(!$this->session){
            $this->session = new Session();
        }
        
        require_once 'Mission.class.php';        
        
        $this->mission = new Mission();

        $this->user = $logUser;
        $this->pass = $logPass;

        if ($this->verifyLogin($facebook, $remember, $twitter)) {

            require 'BCrypt.class.php';
            $bcrypt = new BCrypt();

            // 2019: There are some important security vulns here
            $this->session->newQuery();
            $sqlQuery = "   SELECT password, id 
                            FROM users
                            WHERE BINARY login = ?
                            LIMIT 1";
            $sqlLog = $this->pdo->prepare($sqlQuery);
            $sqlLog->execute(array($this->user));
            
            if ($sqlLog->rowCount() == '1') {

                $dados = $sqlLog->fetchAll();
                   
                if($bcrypt->verify($this->pass, $dados['0']['password']) || $facebook || $remember || $twitter){

                    $log = $this->log;
                    $ranking = $this->ranking;
                    $storyline = $this->storyline;
                    $clan = $this->clan;

                    if(!$facebook && !$twitter){
                    
                        $sql = "SELECT COUNT(*) AS total FROM users_facebook WHERE gameID = ".$dados['0']['id']." LIMIT 1";
                        $total = $this->pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;
                        
                        if($total == 1){
                            $this->session->addMsg('Facebook fail', 'error');
                            return FALSE;
                        }
                        
                        $sql = "SELECT COUNT(*) AS total FROM users_twitter WHERE gameID = ".$dados['0']['id']." LIMIT 1";
                        $total = $this->pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;
                        
                        if($total == 1){
                            $this->session->addMsg('Twitter fail', 'error');
                            return FALSE;
                        }
                        
                    }
                    
                    $sql = "SELECT COUNT(*) AS total FROM users_premium WHERE id = ".$dados['0']['id']." LIMIT 1";
                    $total = $this->pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

                    if($total == 1){
                        $premium = 1;
                    } else {
                        $premium = 0;
                    }
                    
                    $this->session->loginSession($dados['0']['id'], $this->user, $premium, $special);

                    self::loginDatabase($dados['0']['id']);
                    $certsArray = $ranking->cert_getAll();
        
                    $this->mission->restoreMissionSession($dados['0']['id']);

                    $this->session->certSession($certsArray);

                    if($clan->playerHaveClan($dados['0']['id'])){
                        $_SESSION['CLAN_ID'] = $clan->getPlayerClan($dados['0']['id']);
                    } else {
                        $_SESSION['CLAN_ID'] = 0;
                    }

                    $_SESSION['LAST_CHECK'] = new DateTime('now');
                    $_SESSION['ROUND_STATUS'] = $storyline->round_status();

                    if($_SESSION['ROUND_STATUS'] == 1){
                        $log->addLog($dados['0']['id'], $log->logText('LOGIN', Array(0)), '0');
                        $this->session->exp_add('LOGIN');
                    }
                    
                    return TRUE;

                } else {

                    $this->session->addMsg('Username and password doesnt match. Some accounts were lost, sorry!', 'error');
                    return FALSE;

                }
                
            } else {

                $this->session->addMsg('Username and password doesnt match. Some accounts were lost, sorry!', 'error');
                return FALSE;
                
            }
            
        }
    }
    
    private function loginDatabase($id){
        
        $this->session->newQuery();
        $sql = 'SELECT COUNT(*) AS total FROM users_online WHERE id = '.$id.' LIMIT 1';
        if($this->pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total > 0){
            $this->session->newQuery();
            $sql = 'DELETE FROM users_online WHERE id = '.$id.' LIMIT 1';
            $this->pdo->query($sql);
        }
        
        require_once 'RememberMe.class.php';
        $key = pack("H*", 'REDACTED');
        $rememberMe = new RememberMe($key, $this->pdo);
        $rememberMe->remember($id, false, $this->keepalive);
                
        $this->session->newQuery();
        $sql = 'UPDATE users SET lastLogin = NOW() WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array(':id' => $id));
        
        setcookie('logged', '1', time() + 172800);
        
        
    }
    
    private function verifyLogin($fb, $tt, $rm) {

        if($fb || $rm || $tt){
            return TRUE;
        }
        
        if (strlen($this->user) == '0' || strlen($this->pass) == '0') {

            $this->session->addMsg('Some fields are empty.', 'error');
            return FALSE;
            
        } else {

            return TRUE;
            
        }
    }

}

?>
