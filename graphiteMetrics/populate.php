<?php

ini_set('memory_limit', -1);
const CONFIG_FILE = 'config.ini';

class Populate extends Collectable
{

    /**
     * @var string
     */
    public $query;

    /**
     * @var string
     */
    public $result;

    /**
     * @param string $query
     */
    public function __construct($query)
    {
        $this->query = $query;
        $this->config = parse_ini_file(CONFIG_FILE, true);
    }

    /**
     * Validates the CURL response 
     *
     * @param $curl
     * @param $response
     *
     * @return void
     */
    private function validateCurlResponse($curl, $response)
    {
        if ($response === false)
            die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
    }

    private function cleanString($string) {
	   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens
	   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars
	}

    /**
     * Constructs Graphite GET request
     *
     * @param $target
     *
     * @return string
     */
    private function constructGraphiteParams()
    {
        $params = array(
            'until'     => 'now',
            '_uniq'     => $this->config['graphite']['UNIQ'],
            '_salt'     => $this->config['graphite']['SALT'],
            'format'    => 'json',
            'from'      => $this->config['graphite']['TIME_RANGE'],
            'target'    => $this->config['targets'][$this->query],
            );
        return $params;
    }

    private function constructCachetParams($graphite_datapoint)
    {
        $value      = $graphite_datapoint[0];
        $timestamp  = $graphite_datapoint[1];

        $params = array(
            'value'     => $value,
            'timestamp' => $timestamp
            );

        return $params;
    }

    private function cachetGetRequest($url)
    {
        $headers = array();
        $headers[] = 'Accept-Encoding: gzip, deflate';
        $headers[] = 'X-Cachet-Token: '.$this->config['cachet']['TOKEN'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_HTTPHEADER      => $headers,
        ));

        $response = curl_exec($curl);
        $this->validateCurlResponse($curl, $response);

        curl_close($curl);

        return $response;
    }

    private function cachetPostRequest($url, $params, $request = 'POST')
    {
        $params_string = http_build_query($params, '', '&');

        $headers = array();
        $headers[] = 'Accept-Encoding: gzip, deflate';
        $headers[] = 'X-Cachet-Token: '.$this->config['cachet']['TOKEN'];
        
        $curl = curl_init();
        
        $options = array(
            CURLOPT_URL             => $url,
            CURLOPT_POSTFIELDS      => $params_string,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_HTTPHEADER      => $headers,
            CURLINFO_HEADER_OUT     => true
        	);

       	if ($request == 'POST') {
       		$options[CURLOPT_POST] = true;
       	} elseif ($request == 'PUT') {
       		$options[CURLOPT_CUSTOMREQUEST] = 'PUT';
       	}

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $this->validateCurlResponse($curl, $response);

		curl_close($curl);

        return $response;
    }

    private function getComponentStatus($component)
    {
    	$base_url = $this->config['cachet']['BASE_URL'];
    	$endpoint = $this->config['endpoints']['COMPONENTS'];
    	$target = $this->config['components'][$component];
        $url = $base_url . $endpoint . $target;

        $response = $this->cachetGetRequest($url);
        $response = json_decode($response, true);
        return $response['data']['status'];
    }

    private function evalThresholdCondition($value, $threshold_condition)
    {
	 	$conditions = explode('&&', $threshold_condition);

		$result = true;
		foreach ($conditions as $condition) {
			$result = $result & eval("return ". $value . $condition . ";");
			if ($result == false)
				break;
		}

		return $result;
    }

    private function getPointStatus($value)
    {
    	foreach ($this->config['threshold_status'] as $threshold_name => $threshold_status) {
			$threshold_condition = $this->config['thresholds'][$threshold_name][$this->query];
			$result = $this->evalThresholdCondition($value, $threshold_condition);

			if ($result) {
				return $threshold_status;
			}
    	}
    }

    private function updateComponentStatus($value)
    {
        $status = $this->getPointStatus($value);

        $affected_component = $this->config['metric_components'][$this->query];
       	$affected_component_url_suffix = $this->config['components'][$affected_component];
        $affected_component_id = $this->cleanString($affected_component_url_suffix);

        $base_url = $this->config['cachet']['BASE_URL'];
        $endpoint = $this->config['endpoints']['COMPONENTS'];
        $target = $affected_component_url_suffix;
        $url = $base_url . $endpoint . $target;

        $params = array(
        	'id'     => $affected_component_id,
            'status' => $status
            );

        if ($status != 1) {
            $this->cachetPostRequest($url, $params, 'PUT');
        }
        else {
            $current_status = $this->getComponentStatus($affected_component);
            if ($current_status != 1) {
            	$this->cachetPostRequest($url, $params, 'PUT');
            }
        }
    }

    private function fetchGraphiteData()
    {   
        $params = $this->constructGraphiteParams();
        $url = $this->config['graphite']['BASE_URL'].'?'.http_build_query($params, '', '&');

        $username = $this->config['graphite']['USERNAME'];
        $password = $this->config['graphite']['PASSWORD'];

        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_USERPWD         => $username.':'.$password,
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_CONNECTTIMEOUT  => 10,
        ));

        $response = curl_exec($curl);
        $this->validateCurlResponse($curl, $response);

        curl_close($curl);

        return $response;
    }

    private function addMetricPoint($params)
    {
        $base_url = $this->config['cachet']['BASE_URL'];
        $endpoint = $this->config['endpoints']['METRIC_POINTS'];
        $target   = $this->config['metric_points'][$this->query];
        $url = $base_url . $endpoint . $target;

       	$response = $this->cachetPostRequest($url, $params, 'POST');
        return $response;
    }

    public function run()
    {
		echo microtime(true).PHP_EOL;

		$response = json_decode($this->fetchGraphiteData(), true);
		$graphite_datapoint = $response[0]['datapoints'][0];

		if ($this->config['default']['UPDATE_COMPONENT_STATUS'] && array_key_exists($this->query, $this->config['thresholds']['Operational'])) {
			$this->updateComponentStatus($graphite_datapoint[0]);
		}
		elseif (!isset($graphite_datapoint[0])) {
			$graphite_datapoint[0] = -1;
		}

		$params = $this->constructCachetParams($graphite_datapoint);
		$result = $this->addMetricPoint($params);

		$this->result = isset($result);
		$this->setGarbage();
    }
}

class SearchPool extends Pool
{
	/**
	 * @var array
	 */
	public $data = [];

	/**
	 * @return array
	 */
	public function process()
	{
	    // Run this loop as long as we have jobs in the pool
	    while (count($this->work)) {
	        $this->collect(function (Populate $job) {
	            // If a job was marked as done then collect its results
	            if ($job->isGarbage()) {
	                $this->data[$job->query] = $job->result;
	            }

	            return $job->isGarbage();
	        });
	    }

	    // All jobs are done, we can shutdown the pool
	    $this->shutdown();

	    return $this->data;
	}
}

// Create a pool and submit jobs to it
$config = parse_ini_file(CONFIG_FILE, true);
$metrics = $config['metric_points'];
$pool = new SearchPool(count($metrics), Worker::class);
foreach ($metrics as $metric => $_suffix) {
	$pool->submit(new Populate($metric));
}

$data = $pool->process();
var_dump($data);
