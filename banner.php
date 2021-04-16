<?php

$host = "localhost";
$dbname = "banner";
$username = "root";
$password = "root";

try {
	$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

	foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key)
	{
		if (array_key_exists($key, $_SERVER) === true)
		{
			foreach (explode(',', $_SERVER[$key]) as $ip)
			{
				if (filter_var($ip, FILTER_VALIDATE_IP) !== false)
				{
					$ip_address = $ip;
					break;
				}
			}
		}
	}

	$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
	$page_url = $_SERVER['HTTP_REFERER'] ?? $_SERVER['REQUEST_URI'];
	$view_date = date('Y-m-d H:i:s');


	$sql = "SELECT * FROM `views` WHERE `ip_address`=:ip_address AND `user_agent`=:user_agent AND `page_url`=:page_url";

	$statement = $pdo->prepare($sql);
	$statement->execute([
		'ip_address' => $ip_address ?? null,
		'user_agent' => $user_agent,
		'page_url' => $page_url,
	]);
	$rows = $statement->fetchAll();
	$count = (int) reset($rows);

	if($count === 0) {
		$views_count = 1;
		$sql = "INSERT  INTO `views` (`ip_address`, `user_agent`, `view_date`, `page_url`, `views_count`) VALUES (:ip_address, :user_agent, :view_date, :page_url, :views_count)";
	}
	else {
		$row = reset($rows);
		$views_count = $row['views_count'] + 1;
		$view_date = date('Y-m-d H:i:s');
		$sql = "UPDATE `views` SET `view_date` = :view_date, `views_count` = :views_count WHERE `ip_address`= :ip_address AND `user_agent` = :user_agent AND `page_url` = :page_url";
	}


	$statement = $pdo->prepare($sql);

	$data = [
		'ip_address' => $ip_address ?? null,
		'user_agent' => $user_agent,
		'view_date' => $view_date,
		'page_url' => $page_url,
		'views_count' => $views_count,
	];

	$result= $statement->execute($data);

	if($result) {
		$svg = <<<SVG
			<svg width="490" height="245" xmlns="http://www.w3.org/2000/svg">
			
			 <g>
			  <title>Counter</title>
			  <rect stroke="#000" stroke-width="17" rx="33" id="svg_1" height="226.33334" width="473.33335" y="9.49984" x="10.88889" fill="#999999"/>
			  <text font-weight="bold" xml:space="preserve" 
				    x="30%" 
				    y="53%" 
				    dominant-baseline="middle" 
				    text-anchor="middle" 
				    font-family="sans-serif" 
				    font-size="120" 
				    opacity="0.99" 
				    stroke-width="3" 
				    stroke="#000" 
				    fill="#000000">
			        $views_count
			  </text>
			 </g>
			</svg>
		SVG;

		header("Content-type: image/svg+xml");
		echo $svg;

	}
	else {
		throw new Exception('Couldn\'t update the data, please try again.');
	}
}
catch (PDOException | Exception $e) {
//	$msg = $e->getMessage();
	$svg = <<<SVG
		<svg width="490" height="245" xmlns="http://www.w3.org/2000/svg">		
		 <g>
		  <title>Counter</title>
		  <rect stroke="#000" stroke-width="17" rx="33" id="svg_1" height="226.33334" width="473.33335" y="9.49984" x="10.88889" fill="#999999"/>
		  <text font-weight="bold" xml:space="preserve" 
			    x="30%" 
			    y="53%" 
			    dominant-baseline="middle" 
			    text-anchor="middle" 
			    font-family="sans-serif" 
			    font-size="120" 
			    opacity="0.99" 
			    stroke-width="3" 
			    stroke="#000" 
			    fill="#000000">
		        ---
		  </text>
		 </g>
		</svg>
	SVG;

	header("Content-type: image/svg+xml");
	echo $svg;
}

