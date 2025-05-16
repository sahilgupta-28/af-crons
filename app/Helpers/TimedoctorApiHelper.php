<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;

class TimedoctorApiHelper
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => rtrim(config('app.timedoctor_base_url'), '/') . '/',
            'timeout'  => 30,
            'headers'  => [
                'Accept'        => 'application/json',
                'Authorization' => 'JWT ' . config('app.timedoctor_access_token'),
                'Content-Type'  => 'application/json',
            ],
        ]);
    }
    
    public function request(string $method, string $endpoint, array $payload = []): array
    {
        $attempts = 0;
        $maxAttempts = 3;
        $delay = 1; 
    
        do {
            try {
                $options = [];
    
                if (strtolower($method) === 'get') {
                    $options['query'] = $payload;
                } else {
                    $options['json'] = $payload;
                }
    
                $response = $this->client->request(strtoupper($method), ltrim($endpoint, '/'), $options);
    
                return json_decode($response->getBody()->getContents(), true);
            } catch (RequestException | ConnectException $e) {
                $responseBody = $e instanceof RequestException
                    ? $e->getResponse()?->getBody()?->getContents()
                    : null;
    
                $errorData = $responseBody ? json_decode($responseBody, true) : [];
                $errorMessage = $errorData['message'] ?? $e->getMessage();
    
                Log::warning("API attempt " . ($attempts + 1) . " failed: " . $errorMessage);
    
                if (++$attempts >= $maxAttempts) {
                    Log::error("API Request Failed after $maxAttempts attempts: " . $e->getMessage());
                    throw new \Exception("API Request Failed: " . $errorMessage);
                }
    
                sleep($delay);
            } catch (\Throwable $e) {
                Log::error('API Unexpected Error: ' . $e->getMessage());
                throw new \Exception("Unexpected Error: " . $e->getMessage());
            }
        } while ($attempts < $maxAttempts);
    }
    

    public function getApiTimedoctorUsers($company_id, $nextUrl = null)
    {
        $this->check_token();
        if ($nextUrl) {
            return $this->request('get', $nextUrl);
        }
        return $this->request('get', '/api/1.0/users', ['company' => $company_id]);
    }

	public function check_token() {
		if (!config('app.timedoctor_access_token')) {
			throw new \Exception("Access token not found.");
		}
	}


    public function get_tasks_stats_total($company_id, $user_id, $from, $to, $detail = null) {
        $params = [
            'company' => $company_id,
            'user'    => $user_id,
            'from'    => $from,
            'to'      => $to,
            'detail'  => 'false',
        ];
        
        return $this->request('get', '/api/1.0/activity/worklog', $params);
    }

    public function get_timedoctor_user_tasks($company_id, $task_id) {
        $this->check_token();
    
        $allData = [];
        $params = [
            'company' => $company_id,
            'tasks'   => $task_id,
        ];
    
        $endpoint = '/api/1.0/tasks';
        do {
            $response = $this->request('get', $endpoint, $params);
    
            if (!empty($response['data'])) {
                $allData = array_merge($allData, $response['data']);
            }
    
            $next = $response['paging']['next'] ?? null;
    
            if ($next) {
                $parsed = parse_url($next);
                $endpoint = $parsed['path'] ?? '/api/1.0/tasks';
                parse_str($parsed['query'] ?? '', $params);
            }
        } while ($next);
    
        return $allData;
    }

    public function get_timedoctor_user_projects($company_id, $project_id) {
		$this->check_token();
        $params = [
            'company' => $company_id,
        ];
		return $this->request('get', "/api/1.0/projects/$project_id", $params);
	}

}
