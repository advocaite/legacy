<?php
require_once '../classes/PDO.class.php';

class npc_generator
{

    private $json_data = null;
    private $npcList = null;
    private $db = null;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->json_data = file_get_contents("../json/npc.json");
        $this->npcList = json_decode($this->json_data, true);
        $this->db = PDO_DB::factory();
    }

    public function generate_ip()
    {
        // due to php7.1 rand() is now a alias of mt_rand() but if before that better to use mt_rand
        $gameIP1 = rand(0, 255);
        $gameIP2 = rand(0, 255);
        $gameIP3 = rand(0, 255);
        $gameIP4 = rand(0, 255);

        return $gameIP1 . '.' . $gameIP2 . '.' . $gameIP3 . '.' . $gameIP4;
    }

    public function gen_unique_pass($n = 8)
    {
        $str = '';
        for ($i = 0; $i < $n; $i++) {
            if (rand(0, 1)) {
                $str .= chr(rand(ord('A'), ord('Z')));
            } else {
                $str .= chr(rand(ord('0'), ord('9')));
            }
        }
        return $str;
    }

    public function empty_db()
    {

        $this->db->query("DELETE npc, hardware, software, log
					FROM npc 
					LEFT JOIN hardware
					ON 
						hardware.userID = npc.id AND
						hardware.isNPC = 1
					LEFT JOIN software
					ON
						software.userID = npc.id AND
						software.isNPC = 1
					LEFT JOIN log
					ON 
						log.userID = npc.id AND
						log.isNPC = 1
					WHERE 
						npc.npcType != 80");

        $this->db->query("DELETE FROM software_original");
        $this->db->query("DELETE FROM software_running");
        $this->db->query("DELETE FROM npc_key");
        $this->db->query("DELETE FROM npc_info_en");
        $this->db->query("DELETE FROM npc_info_pt");
        $this->db->query("DELETE FROM npc_reset");

	}

	public function add($npcType, $npcInfo, $key){

if(is_null(@$npcInfo['ip'])){
    $npcip = $this->generate_ip();
}else{
    $npcip = $npcInfo['ip'];
}


    $sql = "INSERT INTO npc
           (npctype, npcip, npcpass)
            VALUES
            (".$npcType.", INET_ATON('".$npcip."'), '".$this->gen_unique_pass()."')
            ";

      $this->db->query($sql);
        //echo $npcType.'<br>';

    $npcID = $this->db->lastInsertId();

        foreach ($npcInfo['name'] as $key2 => $language){


            $npcName = $npcInfo['name'][$key2];

    $npcWeb = $npcInfo['web'][$key2];

    $table = 'npc_info_'.$key2;

            $sql = 'INSERT INTO '.$table.'
    (npcid, name, web)
    VALUES
    (:npcid, :name, :web)';
            $data = $this->db->prepare($sql);
            $data->execute(array(
                ':npcid' =>$npcID,
                ':name' => $npcName,
                ':web' => $npcWeb,
            ));
        }

        $sql = "INSERT INTO npc_key
    (npcID, npc_key.key)
    VALUES
    (".$npcID.", '".$key."')";

        $this->db->query($sql);

        $cpu = $npcInfo['hardware']['cpu'];
    $hdd = $npcInfo['hardware']['hdd'];
    $ram = $npcInfo['hardware']['ram'];
    $net = $npcInfo['hardware']['net'];

        $sql = " INSERT INTO hardware
        (userID, name, cpu, hdd, ram, net, isNPC)
    VALUES
    ( ".$npcID.", '', ".$cpu.", ".$hdd.", ".$ram.", ".$net.", '1')";

        $this->db->query($sql);

        $sql = "INSERT INTO log
        (userID, isNPC, text)
    VALUES
    (".$npcID.", 1, 'localhost')";

        $this->db->query($sql);

        $nextScan = rand(1,50);
        $sql = "INSERT INTO npc_reset
    (npcID, nextScan)
    VALUES
    (".$npcID.", DATE_ADD(NOW(), INTERVAL ".$nextScan." HOUR))";

        $this->db->query($sql);


    }

    /**
     * find better faster way to do this ;) but at least it works.
     */
    public function generate_npc(){
        $this->empty_db();
        foreach ($this->npcList as $key => $npctype){

            if (array_key_exists('hardware', $npctype)) {
                if (array_key_exists('type', $npctype)) {
                    /**
                     *
                     * ISP,MD,EVILCORP,SAFENET,FBI,NSA,BITCOIN,DC,TORRENT
                     *
                     */
                    //var_dump($this->npcList[$key]);
                  //  echo 'Adding Special NPC, type='.$this->npcList[$key]['type'].', info='.($this->npcList[$key]).', key='.$key;
                    $this->add($this->npcList[$key]['type'], $this->npcList[$key], $key);
                }
            }
            // WHOIS, BANK, PUZZLE, NPC
            if (!array_key_exists('hardware', $npctype)) {
                if (array_key_exists('type', $npctype)) {
                    $required = array('LEVEL1', 'LEVEL2', 'LEVEL3');
                    if (count(array_intersect_key(array_flip($required), $npctype)) === 0) {
                        foreach ($this->npcList[$key] as $key2 => $array) {
                            //echo 'Adding Special NPC, type='.$this->npcList[$key]['type'].', info='.($this->npcList[$key]).', key='.$key;
                            if ($key2 != 'type') {
                                $this->add($this->npcList[$key]['type'], $this->npcList[$key][$key2], $key . "/" . $key2);
                            }
                        }
                    }
                }
            }
            // HIRE MISSIONS NPC
            if (!array_key_exists('hardware', $npctype)) {
                if (!array_key_exists('type', $npctype)) {
                    if (array_key_exists('LEVEL1', $npctype)) {
                        //var_dump($this->npcList[$key]);
                       // echo 'Adding Special NPC, type=' . $this->npcList[$key]['type'] . ', info=' . ($this->npcList[$key]) . ', key=' . $key;
                        foreach ($this->npcList[$key] as $key2 => $array) {

                            //echo 'Adding Special NPC, type='.$this->npcList[$key]['type'].', info='.($this->npcList[$key]).', key='.$key;
                            if ($this->npcList[$key][$key2]['type'] != 61) {
                               //var_dump($this->npcList[$key][$key2]);
                                foreach ($this->npcList[$key][$key2] as $key3 => $array) {
                                    if ($key3 != 'type') {
                                        //var_dump($this->npcList[$key][$key2][$key3]);
                                        $this->add($this->npcList[$key][$key2]['type'], $this->npcList[$key][$key2][$key3], $key . "/LEVEL1/" . $key3);
                                    }
                                }
                            }
                        }
                    }
                    if (array_key_exists('LEVEL2', $npctype)) {
                        //var_dump($this->npcList[$key]);
                       // echo 'Adding Special NPC, type=' . $this->npcList[$key]['type'] . ', info=' . ($this->npcList[$key]) . ', key=' . $key;
                        //$this->add($this->npcList[$key]['type'], $this->npcList[$key][$key2], $key . "/LEVEL2/" . $key2);
                        foreach ($this->npcList[$key] as $key2 => $array) {

                            //echo 'Adding Special NPC, type='.$this->npcList[$key]['type'].', info='.($this->npcList[$key]).', key='.$key;
                            if ($this->npcList[$key][$key2]['type'] != 61) {
                                //var_dump($this->npcList[$key][$key2]);
                                foreach ($this->npcList[$key][$key2] as $key3 => $array) {
                                    if ($key3 != 'type') {
                                        //var_dump($this->npcList[$key][$key2][$key3]);
                                        $this->add($this->npcList[$key][$key2]['type'], $this->npcList[$key][$key2][$key3], $key . "/LEVEL2/" . $key3);
                                    }
                                }
                            }
                        }
                    }
                    if (array_key_exists('LEVEL3', $npctype)) {
                       // var_dump($this->npcList[$key]);
                       // echo 'Adding Special NPC, type=' . $this->npcList[$key]['type'] . ', info=' . ($this->npcList[$key]) . ', key=' . $key;
                      //  $this->add($this->npcList[$key]['type'], $this->npcList[$key][$key2], $key . "/LEVEL3/" . $key2);
                        foreach ($this->npcList[$key] as $key2 => $array) {

                            //echo 'Adding Special NPC, type='.$this->npcList[$key]['type'].', info='.($this->npcList[$key]).', key='.$key;
                            if ($this->npcList[$key][$key2]['type'] != 61) {
                                //var_dump($this->npcList[$key][$key2]);
                                foreach ($this->npcList[$key][$key2] as $key3 => $array) {
                                    if ($key3 != 'type') {
                                        //var_dump($this->npcList[$key][$key2][$key3]);
                                        $this->add($this->npcList[$key][$key2]['type'], $this->npcList[$key][$key2][$key3], $key . "/LEVEL3/" . $key3);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

    }


}
$test = new npc_generator();
$test->generate_npc();