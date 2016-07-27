<?php

ini_set('memory_limit', -1);
const CONFIG_FILE = 'config.ini';
require_once('redis_helper.php');

// Prepend a base path if Predis is not available in your "include_path".
require 'Predis/Autoloader.php';

class Populate
{
    /**
     * Constructor: connects to Redis server and loads the config file
     */
    public function __construct()
    {
        Predis\Autoloader::register();
        $this->client = new Predis\Client([
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ]);

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

    /**
     * Strips off all the special characters
     *
     * @param string
     *
     * @return string
     */
    private function cleanString($str) {
	   $str = str_replace(' ', '-', $str);     // Replaces all spaces with hyphens
	   return preg_replace('/[^A-Za-z0-9\-]/', '', $str);      // Removes special chars
    }

    /**
     * Converts config name format to title case
     *
     * @param string
     *
     * @return string
     */
    private function titleCase($str) {
      return ucwords(strtolower(str_replace('_', ' ', $str)));
    }

    /**
     * Constructs Graphite GET params
     *
     * @return array
     */
    private function constructGraphiteParams()
    {
        $params = array(
            'until'     => 'now',
            'format'    => 'json',
            'from'      => $this->config['graphite']['TIME_RANGE'],
            );

        return $params;
    }

    /**
     * Constructs Cachet metric point params
     *
     * @param array     $graphite_datapoint
     *
     * @return array    $params
     */
    private function constructCachetMetricPointParams($graphite_datapoint)
    {
        $value      = $graphite_datapoint[0];
        $timestamp  = $graphite_datapoint[1];

        $params = array(
            'value'     => $value,
            'timestamp' => $timestamp,
            );

        return $params;
    }

    /**
     * Perform Cachet GET request
     *
     * @param string    $url
     *
     * @return string   $response
     */
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

    /**
     * Perform Cachet POST/PUT request
     *
     * @param string    $url
     * @param array     $params
     * @param string    $request
     *
     * @return string   $response
     */
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

    /**
     * Fetch data from Graphite for all the metrics in config.ini
     *
     * @return string   $response
     */
    public function fetchGraphiteData()
    {
        $params = $this->constructGraphiteParams();

        $metrics = $this->config['metrics'];
        $targets = '';
        foreach ($metrics as $metric => $_id) {
            $targets = $targets . '&target=' . $this->config['targets'][$metric]; 
        }

        $params_string = http_build_query($params, '', '&') . $targets;
        $url = $this->config['graphite']['BASE_URL'] . '?' . $params_string;

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

    /**
     * Evaluate threshold condition for the metric point value.
     * Threshold conditions set in the config file under header: 'thresholds'
     *
     * @param int       $value
     * @param string    $threshold_condition
     *
     * @return bool     $result
     */
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

    /**
     * Calculates status based on the metric point value and threshold conditions
     *
     * @param int       $value
     * @param string    $target
     *
     * @return int      $threshold_status
     */
    private function getMetricPointStatus($value, $target)
    {
    	foreach ($this->config['threshold_status'] as $threshold_name => $threshold_status) {
			$threshold_condition = $this->config['thresholds'][$threshold_name][$target];
			$result = $this->evalThresholdCondition($value, $threshold_condition);

			if ($result) {
				return $threshold_status;
			}
    	}
    }

    /**
     * Triggers incident based on metric point status for the specific target argument
     * Sets Redis values for metric_component_incident hash
     *
     * @param int       $status
     * @param string    $target
     *
     * @return void
     */
    private function triggerIncident($status, $target)
    {
        $base_url = $this->config['cachet']['BASE_URL'];
        $endpoint = $this->config['endpoints']['INCIDENTS'];

        $affected_components = array_map('trim', explode(',', $this->config['metric_components'][$target]));
        $affected_components_ids = array_map(function($item) {
                return $this->cleanString($this->config['components'][$item]);
            }, $affected_components);

        var_dump($affected_components);
        var_dump($affected_components_ids);
        
        // incident_status => IDENTIFIED or FIXED based upon whether threshold_status is Operational or not
        $incident_status = ($status == $this->config['threshold_status']['Operational']) ? $this->config['incident_details']['STATUS_FIXED'] : $this->config['incident_details']['STATUS_IDENTIFIED'];

        /** 
         *  Redis metric_component_incident hash
         *  affected_components_ids are underscore separated component ids
         *  E.g, 1, 1_2, 1_2_3
         *  metric_component_incident:<metric_id> <affected_components_ids> <incident_id>
         */
        $target_id = $this->cleanString($this->config['metrics'][$target]);
        $metricHashKey = 'metric_component_incident:' . $target_id;
        $affected_components_field = implode('_', $affected_components_ids);
        $saved_incident_id = NULL;

        if (hashFieldExists($this->client, $metricHashKey, $affected_components_field)) {
            $saved_incident_id = getHashValue($this->client, $metricHashKey, $affected_components_field);
        }

        /**
         *  If there is no previous incident for this metric and affected components,
         *  create one with appropriate incident status and visibility
         *  Else if incident is already created, update incident status if FIXED
         */
        if (!isset($saved_incident_id) && $status != $this->config['threshold_status']['Operational']) {

            $affected_components_string = $this->titleCase(implode(', ', $affected_components));
            $name = $this->config['incident_details']['NAME'].$affected_components_string;

            $threshold_name = array_search($status, $this->config['threshold_status']);
            $messages = array(
                $this->config['incident_details']['MESSAGE_IDENTIFIED'],
                );
            $message = implode("\r\n\n", $messages);

            $params = array(
            	'name' 		=> $name,
            	'message'	=> $message,
            	'status'	=> $incident_status,
            	'visible'	=> $this->config['metric_incident_visibility'][$target],
            	);

            $url = $base_url . $endpoint;

           	$response = $this->cachetPostRequest($url, $params, 'POST');

            $incident_id = json_decode($response, true)['data']['id'];
            setHashValue($this->client, $metricHashKey, $affected_components_field, $incident_id);

        } elseif (isset($saved_incident_id) && $incident_status == $this->config['incident_details']['STATUS_FIXED']) {

            $messages = array(
                $this->config['incident_details']['MESSAGE_IDENTIFIED'],
                $this->config['incident_details']['MESSAGE_FIXED'],
                );
            $message = implode("\r\n\n", $messages);

            $params = array(
                'id'        => $saved_incident_id,
                'message'   => $message,
                'status'    => $incident_status,
                'visible'   => $this->config['metric_incident_visibility'][$target],
                );
            
            $url = $base_url . $endpoint . '/'. $saved_incident_id;

            $response = $this->cachetPostRequest($url, $params, 'PUT');
            var_dump(json_decode($response, true));
            deleteHashField($this->client, $metricHashKey, $affected_components_field);
        }
    }

    /**
     * Updates component(s) status based on metric point status for the specific target argument
     * Sets Redis values for components hash
     *
     * @param int       $status
     * @param string    $target
     *
     * @return void
     */
    private function updateComponentStatus($status, $target)
    {
        $affected_components = array_map('trim', explode(',', $this->config['metric_components'][$target]));
        foreach ($affected_components as $affected_component) {
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

            $componentsHashKey = 'components:' . $affected_component_id;
            $current_status = getHashValue($this->client, $componentsHashKey, 'status');

		    if (!isset($current_status) || $status != $current_status) {
		        $this->cachetPostRequest($url, $params, 'PUT');
                setHashValue($this->client, $componentsHashKey, 'status', $status);
		    }
        }
    }

    /**
     * Cachet POST request for adding the metric point
     *
     * @param array     $params
     * @param string    $target
     *
     * @return void
     */
    private function addMetricPoint($params, $target)
    {
        $base_url = $this->config['cachet']['BASE_URL'];
        $endpoint = $this->config['endpoints']['METRICS'];
        $target   = $this->config['metrics'][$target];
        $endpoint_suffix = $this->config['endpoints']['METRIC_POINTS'];
        $url = $base_url . $endpoint . $target . $endpoint_suffix;

       	$this->cachetPostRequest($url, $params, 'POST');
    }

    /**
     * Executes series of steps to populate Cachet with metric values,
     * update component(s) status and trigger incidents
     *
     * @param array     $responses
     *
     * @return void
     */
    public function process($responses)
    {
        foreach (array_keys($this->config['metrics']) as $index => $target) {
            $graphite_datapoint = $responses[$index]['datapoints'][0];
            if (isset($graphite_datapoint[0]) && array_key_exists($target, $this->config['thresholds']['Operational'])) {
                $status = $this->getMetricPointStatus($graphite_datapoint[0], $target);

                if ($this->config['default']['UPDATE_COMPONENT_STATUS']) {
                    $this->updateComponentStatus($status, $target);
                }

                if ($this->config['default']['TRIGGER_INCIDENTS']) {
                    $this->triggerIncident($status, $target);
                }

            } elseif (!isset($graphite_datapoint[0])) {
                $graphite_datapoint[0] = -1;
            }

            $params = $this->constructCachetMetricPointParams($graphite_datapoint);
            $this->addMetricPoint($params, $target);
        }
    }
}

/**
 * Create a new Populate instance, fetch Graphite data and execute process()
 */
$populate = new Populate();
$responses = json_decode($populate->fetchGraphiteData(), true);
$populate->process($responses);
