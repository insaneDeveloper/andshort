<?php
class Sql_Logic{

	const REGISTER_URL="INSERT IGNORE INTO uris( 
	`id` 
	,`shortUrl` 
	,`originalUrl` 
	,`queryString` 
	,`createdDate` 
	,`createdTime`
	,`passwordProtected`
	,`visitedTimes` ) 
	VALUES( '', '%s', '%s', '%s', '%s', NOW( ) , NOW( ) , '0')";
	
	
	const REGISTER_USER_URL="INSERT IGNORE INTO uris( 
	`id`
	,`userid`
	,`shortUrl` 
	,`originalUrl` 
	,`queryString` 
	,`createdDate` 
	,`createdTime`
	,`passwordProtected`
	,`visitedTimes` ) 
	VALUES( '', '%d', '%s', '%s', '%s', NOW( ) , NOW( ) , NOW( ) , '0')";
	
	
	
	
	const NEW_CATEGORY="INSERT into categories(name,created) VALUES('%s',NOW())";
	const NEW_USER_CATEGORY="INSERT into categories(name,userid,created) VALUES('%s','%d',NOW())";
	const NEW_USER_PRIVATE_CATEGORY="INSERT into categories(name,userid,public,created) VALUES('%s','%d',0,NOW())";
	
	const CATEGORY_USER_EXISTS="SELECT * from categories where name = '%s' and userid = '%d' ";
	const TIE_USER_URL_CATEGORY="insert ignore into cat2uris(uri_id,cat_id) VALUES('%d','%d')";
	const DROP_CATEGORIE="DELETE from categories where id='%d' and userid='%d'";
	const DROP_URI="DELETE from uris where id='%d' and userid='%d'";
	const TOGGLE_VISIBILITY = "UPDATE categories SET public=IF(public=0,1,0) WHERE id='%d' LIMIT 1";
	const TOGGLE_URI_VISIBILITY = "UPDATE uris SET public=IF(public=0,1,0) WHERE id='%d' LIMIT 1";
	
	const GET_CATEGORIE_BY_UID="SELECT id,name,created,IF(public=1,'public','private') as 'currentState' from categories where id='%d'";
	const GET_URI_BY_UID="SELECT id,userid,createdDate,IF(public=1,'public','private') as 'currentState' from uris where id='%d'";
	
	const FETCH_CATEGORIES_BY_UID="SELECT DISTINCT CA.name, IFNULL(COUNT( CU.uri_id ),0) AS 'countUris', CA.id AS 'cat_id', IF( CA.public =1, 'public', 'private' ) AS 'visibility'	
	FROM categories CA LEFT JOIN cat2uris CU ON CU.cat_id=CA.id
	WHERE userid = %d
	GROUP BY CA.id
	
	UNION SELECT CA2.name, COUNT( CA2.id ) AS 'countUris', CA2.id AS 'cat_id', IF( CA2.public =1, 'public', 'private' ) AS 'visibility'
	FROM categories CA2
	WHERE CA2.global =1
	GROUP BY CA2.id";
	
	const FIND_URL='SELECT shortUrl,originalUrl FROM uris WHERE originalUrl="%s"';
	const FIND_URL_BY_SHORT='SELECT shortUrl,visitedTimes,originalUrl,uaTracker,passwordProtected FROM uris WHERE shortUrl="%s"';
	const FIND_USER_BY_EMAIL='SELECT * FROM users WHERE email="%s"';
	const NEW_USER='INSERT INTO users(username,email,password,secretKey,actkey,createdDate) VALUES("%s","%s","%s","%s","%s",NOW())';
	
	const GET_USER_LOGIN='SELECT id as "userid",username,email,website from users where email="%s" and password="%s" limit 1';
	const GET_USER_SOCIAL_AND_PREFERENCES='SELECT uri_type,uri from user_places WHERE userid = "%d" ';
	
	const ACTIVATE_USER='update users set confirmedMail=1 WHERE actkey = "%s" ';
	const FETCH_USER_BY_ACTKEY='select * from users WHERE actkey = "%s" limit 1';
	
	const FETCH_URIS_BY_USER=' SELECT 
			IFNULL(CAT.name,"") as categorie
			, uris.id
			, IF(uris.public=1,"public","private") as "visibility"
			, userid
			, shortUrl
			, shortUrl AS shortId
			, originalUrl
			, CONCAT(SUBSTRING(originalUrl,1,30)," ... ") as originalUrlSubstring
			, queryString
			, createdDate
			, createdTime
			, uaTracker
			, visitedTimes
			, passwordProtected
			, lastVisited
			, visitedTimes AS hits
			
			FROM uris LEFT JOIN ( SELECT CA.name,CU.uri_id FROM  `cat2uris` CU INNER JOIN categories CA ON CA.id = CU.cat_id AND userid = %d  )  as CAT
			ON CAT.uri_id = uris.id
			WHERE userid = %d ORDER BY uris.id DESC';

	const TOP10URL='SELECT shortUrl,originalUrl,createdDate,visitedTimes FROM uris ORDER BY visitedTimes DESC LIMIT 10 ';
	const UPDATE_VISITED_TIMES='UPDATE uris SET visitedTimes=visitedTimes+1 WHERE shortUrl="%s" ';
	
}
?>