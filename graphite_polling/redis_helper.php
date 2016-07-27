<?php 

function openRedisConnection($client, $hostName, $port) { 
	$client->connect($hostName, $port);
	return $client;
} 

function setHashValue($client, $key, $field, $value) { 
    try { 
		$client->hset($key, $field, $value);
	} catch(Exception $e) { 
		echo $e->getMessage(); 
	} 
} 

function getHashValue($client, $key, $field) { 
    try {
		return $client->hget($key, $field);
	} catch(Exception $e) { 
		echo $e->getMessage(); 
	}
} 

function deleteHashField($client, $key, $field) { 
    try { 
		$client->hdel($key, $field);
	} catch(Exception $e) { 
		echo $e->getMessage(); 
	} 
}

function deleteKey($client, $key) {
    try { 
		$client->del($key);
	} catch(Exception $e) { 
		echo $e->getMessage(); 
	} 	
}

function keyExists($client, $key) {
	try {
		return $client->exists($key);
	} catch (Exception $e) {
		echo $e->getMessage(); 
	}
}

function hashFieldExists($client, $key, $field) {
	try {
		return $client->hexists($key, $field);
	} catch (Exception $e) {
		echo $e->getMessage();
	}
}

?>
