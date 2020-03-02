<?php
require_once 'classes/PDO.class.php';
/**
 * profile_generator class converted from profile_generator.py
 * all python script that the game us wil be converted into php
 *
 */
class Profile_Generator
{

private $badge_data = null;
// Convert to array
private $badgeList = null;
private $userID = null;
private $lang = 'en';
private $savefile = null;
private $db = null;

    /**
     * __construct
     */
    public function __construct($id = null, $lang = 'en')
    {
        $this->badge_data = file_get_contents($_SERVER['DOCUMENT_ROOT']."/json/badges.json");
        $this->badgeList = json_decode($this->badge_data, true);
        $this->userID = $id;
        $this->lang = $lang;



        $this->db = PDO_DB::factory();

    }

    /**
     * @function DisplayTotalTime
     * This function formats time in a nice way
     * Used mainly for time spent
     * eg: last_updated_time - current time = timecount
     *
     * @param mixed $timecount
     *
     * @return string
     */
    public function DisplayTotalTime($timecount)
    {
        $tc_year        = 365 * 24 * 60 * 60;
        $tc_day         = 24 * 60 * 60;
        $tc_hour        = 60 * 60;
        $tc_minutes     = 60;
        $TotalCountEcho = "";
        if ($timecount > $tc_year) {
            $tc_y      = floor($timecount / $tc_year);
            $timecount = $timecount - ($tc_year * $tc_y);
            $TotalCountEcho .= number_format($tc_y) . "y:";
        }
        if ($timecount > $tc_day) {
            $tc_d      = floor($timecount / $tc_day);
            $timecount = $timecount - ($tc_day * $tc_d);
            $TotalCountEcho .= number_format($tc_d) . "d:";
        }
        if ($timecount > $tc_hour) {
            $tc_h      = floor($timecount / $tc_hour);
            $timecount = $timecount - ($tc_hour * $tc_h);
            $TotalCountEcho .= number_format($tc_h) . "h:";
        }
        if ($timecount > $tc_minutes) {
            $tc_m      = floor($timecount / $tc_minutes);
            $timecount = $timecount - ($tc_minutes * $tc_m);
            $TotalCountEcho .= number_format($tc_m) . "m:";
        }
        $TotalCountEcho .= number_format($timecount) . "s";
        return $TotalCountEcho;
    }

    function getbadgeinfo($badgeID = 1)
    {
        return $this->badgeList[$badgeID];
    }

    function save($html)
    {
        $this->savefile = fopen($_SERVER['DOCUMENT_ROOT']."/html/profile/" . $this->userID . "_" . $this->lang . ".html", "w") or die("Unable to open file!");
        fwrite($this->savefile, $html);
        fclose($this->savefile);
    }

    function generateProfile()
    {

         $sql = "SELECT 
					users.login, users.premium, clan.clanID, clan.name as clanName, clan.nick as clanTag, clan.createdBy as clanOwner, ranking_user.rank as ranking,  users_stats.dateJoined AS gameAge,
					users_stats.exp as reputation, users_stats.timeplaying as timePlaying, users_stats.hackCount as hackedcount, users_stats.ddosCount, users_stats.warezSent, users_stats.spamSent,
					users_stats.ipResets, users_stats.moneyEarned, users_stats.moneyTransfered, users_stats.moneyHardware, users_stats.moneyResearch, users_stats.profileViews,
					(SELECT COUNT(*) FROM missions_history WHERE missions_history.userID = users.id AND completed = 1) AS missionCount, users_admin.userID as admin
				FROM users
				LEFT JOIN clan_users
				ON clan_users.userID = users.id
				LEFT JOIN clan 
				ON clan.clanID = clan_users.clanID
				INNER JOIN users_stats
				ON users_stats.uid = users.id
				LEFT JOIN ranking_user
				ON ranking_user.userID = users.id
				LEFT JOIN users_admin
				ON users_admin.userID = users.id
				WHERE users.id = ".$this->userID."
				LIMIT 1 ";
        $query = $this->db->query($sql)->fetch();

        //return $query;
        // get first time ranking and set rank based on count of users ( hindsight: this is not the best way to do this )
        if($query['ranking'] == -1)
        {
            $sql = " SELECT COUNT(*) AS total 
                FROM ranking_user";
            $rankcount = $this->db->query($sql)->fetch();
            $ranking = $rankcount['total'];

        }
        //CLAN STUFF
        if (!is_null($query['clanname']) ) {
            $masterBadge = '';
            $clanID = $query['clanid'];
            $clanTag = $query['clantag'];
            $clanName = $query['clanname'];
            if ($query['clanowner'] == $this->userID) {
                $masterBadge = '<span class="label label-info right"> Master</span>';
            }
            $nick = '['.$query['clantag'].'] '.$query['login'];
            $clan = '<tr>
                                    <td><span class="item">Clan</span></td>
                                    <td><a href="clan?id='.$clanID.'" class="black">['.$clanTag.'] '.$clanName.'</a>'.$masterBadge.'</td>
                                </tr>';
        }else {
            $nick = $query['login'];
            $clan = '';
        }
        //FRIEND STUFF
        $sql = "SELECT 
						COUNT(*) AS total 
					FROM users_friends 
					WHERE userID = ".$this->userID." OR friendID = ".$this->userID." ";
        $friendcount = $this->db->query($sql)->fetch();

        $totalFriends = $friendcount['total'];
        $friendsHTML = '';
        $friendClanHTML= '';
        if ( $totalFriends > 0){
            $sql = "SELECT 
						userID, friendID 
					FROM users_friends 
					WHERE userID = ".$this->userID." OR friendID = ".$this->userID."
					ORDER BY dateAdd ASC 
					LIMIT 5";
            $friendquery = $this->db->query($sql)->fetchAll();

            foreach($friendquery as  $friend) {

                $sql = "SELECT 
							login as friendName, 
							cache.reputation as friendReputation, 
							ranking_user.rank as friendRank, 
							clan.name as friendClanName, clan.clanID as friendClanID 
						FROM users 
						LEFT JOIN cache 
						ON cache.userID = users.id 
						LEFT JOIN ranking_user 
						ON ranking_user.userID = users.id 
						LEFT JOIN clan_users
						ON clan_users.userID = users.id 
						LEFT JOIN clan 
						ON clan.clanID = clan_users.clanID 
						WHERE users.id = ".$friend['friendid']." 
						LIMIT 1";

                $freindstat = $this->db->query($sql)->fetch();

                if (!is_null($freindstat['friendclanname'])){
                    $friendClanHTML .= '<span class="he16-clan heicon"></span>
											<small><a href="clan?id='.$freindstat['friendclanid'].'">'.$freindstat['friendclanname'].'</a></small>';
                }

                $friendPic = 'images/profile/thumbnail/'.md5($freindstat["friendname"].$friend['friendid']).'.jpg';
                if (file_exists($friendPic)) {
                    $friendPic = 'http://'.$_SERVER['HTTP_HOST'].'/images/profile/unsub.jpg';
                }


                $friendsHTML .= '
                        <ul class="list">
                            <a href="profile?id='.$friend['userid'].'">
                                <li  class="li-click">
                                    <div class="span2 hard-ico">
                                        <img src="'.$friendPic.'">
                                    </div>
                                    <div class="span10">
                                        <div class="list-ip">
                                            '.$freindstat["friendname"].'
                                        </div>
                                        <div class="list-user">
                                            <span class="he16-reputation heicon"></span>
                                            <small>'.number_format($freindstat["friendreputation"]).'</small>
                                            <span class="he16-ranking heicon"></span>
                                            <small>#'.number_format($freindstat["friendrank"]).'</small>'.$friendClanHTML.'
                                        </div>
                                    </div>
                                    <div style="clear: both;"></div>
                                </li>
                            </a>
                        </ul>
                        ';

                $friendsHTML .= '<div class="center">';

                if ($totalFriends > 5) {
                    $friendsHTML .= '<a href="profile?id=' . $this->userID . '&view=friends" class="btn btn-inverse">View all</a>&nbsp;&nbsp;';
                }
            }

            $friendsHTML .= '<a href="profile?view=friends&add='.$this->userID.'" class="btn btn-success add-friend" value="'.$this->userID.'">Add Friend</a></div>';


        }else{

            $friendsHTML = '<div class="center">';
            $friendsHTML .= 'Oh no! This user has no friends :(<br/><br/>';

	        $friendsHTML .= '<a href="profile?view=friends&add='.$this->userID.'" class="btn btn-success add-friend" value="'.$this->userID.'">Add Friend</a></div>';
            $friendClanHTML= '';

        }

        //BADGE STUFF


        $htmlBadges = '';

        $sql = "SELECT 
						users_badge.badgeID, 
						COUNT(users_badge.badgeID) as badgeTotal 
					FROM users_badge 
					JOIN badges_users 
					ON badges_users.badgeID = users_badge.badgeID
					WHERE users_badge.userID = ".$this->userID." 
					GROUP BY users_badge.badgeID 
					ORDER BY badges_users.priority, badges_users.badgeID";
        $badgequery = $this->db->query($sql)->fetchAll();
        //var_dump($badgequery);
        $totalBadges = count($badgequery);

        if ($totalBadges == 0) {
            $htmlBadges .= 'This player has no badges.';
        }else{

            foreach($badgequery as  $badge) {

		        $badgeInfo = $this->getbadgeinfo($badge['badgeid']);
               // var_dump($badgeInfo);
            $badgeStr = '<strong>'.$badgeInfo['name'].'</strong>';
            if ($badgeInfo['desc']){
            $badgeStr .= ' - ' . $badgeInfo['desc'];
                }
                    if ($badgeInfo['collectible']) {
                        $badgeStr .= '<br/><br/> Awarded '.$badge["badgetotal"].' time Awarded '.$badge["badgetotal"].' times';
                    }
            $htmlBadges .= '<img src="http://'.$_SERVER['HTTP_HOST'].'/images/badges/'.$badge["badgeid"].'.png" class="profile-tip" title="'.$badgeInfo['name'].'" value="'.$badge["badgeid"].'"/>';
                }
            }

            //if the user is a staff member then we give them the staff badge
            $staffBadge ='';
            if($query['admin']) {
                $staffBadge = '<span class="label label-important">Staff</span>';
            }

            $pic = 'images/profile/thumbnail/'.md5($nick.$this->userID).'.jpg';
            if (!file_exists($pic)) {
                $pic = 'http://'.$_SERVER['HTTP_HOST'].'/images/profile/unsub.jpg';
            }

        $html = '
	<span id="modal"></span>
	<div class="widget-box">
		<div class="widget-title">
			<span class="icon"><i class="he16-pda"></i></span>
			<h5>'.$nick.'</h5>
			'.$staffBadge.'
		</div>
		<div class="widget-content nopadding">
			<table class="table table-cozy table-bordered table-striped table-fixed">
				<tbody>
					<tr>
						<td><span class="item">Reputation</span></td>
						<td>'.number_format($query['reputation']).' <span class="small">(Ranked #'.number_format($ranking).')</span></td>
					</tr>
					<tr>
						<td><span class="item">Age</span></td>
						<td>'.$this->DisplayTotalTime(time() - strtotime($query['gameage']) ).'</td>
					</tr>
					<tr>
						<td><span class="item">Time playing</span></td>
						<td>'.$this->DisplayTotalTime( time() - strtotime($query['timeplaying']) /** 60*/).'</td>
					</tr>
					'.$clan.'
				</tbody>
			</table>
		</div>
	</div>
	<div class="widget-box">
		<div class="widget-title">
			<span class="icon"><i class="he16-stats"></i></span>
			<h5>Stats</h5>
		</div>
		<table class="table table-cozy table-bordered table-striped table-fixed">
			<tbody>
				<tr>
					<td><span class="item">Hack count</span></td>
					<td>'.number_format($query['hackedcount']).'</td>
				</tr>
				<tr>
					<td><span class="item">IP Resets</span></td>
					<td>'.number_format($query['ipresets']).'</td>
				</tr>
				<tr>
					<td><span class="item">Servers used to DDoS</span></td>
					<td>'.number_format($query['ddoscount']).'</td>
				</tr>
				<tr>
					<td><span class="item">Spam sent</span></td>
					<td>'.number_format($query['spamsent']).' mails</td>
				</tr>
				<tr>
					<td><span class="item">Warez uploaded</span></td>
					<td>'.number_format($query['warezsent']).' GB</td>
				</tr>
				<tr>
					<td><span class="item">Missions completed</span></td>
					<td>'.number_format($query['missioncount']).'</td>
				</tr>
				<tr>
					<td><span class="item">Profile clicks</span></td>
					<td>'.number_format($query['profileviews']).'</td>
				</tr>
				<tr>
					<td><span class="item">Money earned</span></td>
					<td><font color="green">$'.number_format($query['moneyearned']).'</font></td>
				</tr>
				<tr>
					<td><span class="item">Money transferred</span></td>
					<td><font color="green">$'.number_format($query['moneytransfered']).'</font></td>
				</tr>
				<tr>
					<td><span class="item">Money spent on hardware</span></td>
					<td><font color="green">$'.number_format($query['moneyhardware']).'</font></td>
				</tr>
				<tr>
					<td><span class="item">Money spent on research</span></td>
					<td><font color="green">$'.number_format($query['moneyresearch']).'</font></td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="center"><a class="btn btn-inverse center" type="submit">Switch to All-Time stats</a></div>
</div>
<div class="span4">
	<div class="widget-box">
		<div class="widget-title">
			<span class="icon"><span class="he16-profile"></span></span>
			<h5>Photo & Badges</h5>
			<span class="label label-info">'.$totalBadges.'</span>
		</div>
		<div class="widget-content padding noborder">
	        <div class="span12">
				<div class="span12" style="text-align: center; margin-right: 15px; margin-bottom: 5px;">
					<img src="'.$pic.'">
				</div>
                <div class="row-fluid">
                    <div class="span12 badge-div">
                        '.$htmlBadges.'
                	</div>
            	</div>
            </div>
		</div>
		<div style="clear: both;" class="nav nav-tabs">&nbsp;</div>
	</div>
<div class="widget-box">
	<div class="widget-title">
		<span class="icon"><i class="he16-clan"></i></span>
		<h5>Friends</h5>
		<a href="profile?id='.$this->userID.'&view=friends"><span class="label label-info">'.$totalFriends.'</span></a>
	</div>
	<div class="widget-content padding">
	'.$friendsHTML.'
	</div>';


        $this->save($html);

        return $html;
    }
}

/**
 * below uncomment to test profile generation
 * so far its now 100% converted and we just call
 *
 * $profilegenerator = new ProfileGenerator($userid);
 *
 * you see this is much nicer in my opinion game is php so all functions should be php ;)
 * unless someone can explain why?
 */
//$profilegenerator = new ProfileGenerator(3);
//echo $profilegenerator->generateProfile();


