<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TimedoctorService;
use Carbon\Carbon;
use App\Helpers\TimedoctorApiHelper;
use Illuminate\Support\Facades\Log;
use App\Helpers\GoogleSheetSyncHelper;

class TimedoctorCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timedoctor-cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated timeentries from google sheet synchronization.';

    protected $timedoctorService;
    protected $companyId;
    protected $getTimedoctor; 

    public function __construct(TimedoctorService $timedoctorService)
    {
        parent::__construct();

        $this->timedoctorService = $timedoctorService;

        $getTimedoctor = $this->timedoctorService->getTimeDoctor();
        $this->companyId         = $getTimedoctor->timedoctor_company_id;
        $this->google_sheet_id   = $getTimedoctor->google_sheet_id;
        $this->timedoctorService = $timedoctorService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Timedoctor Cron Starts');
        $saveTimedoctorUsers =  $this->timedoctorService->saveTimedoctorUsers($this->companyId);

        $fetchUsersTaskTime = $this->fetchUsersTaskTime();
        Log::info('Fetch Users Task Time functions Hitted');

        $googleSheetHelper = new GoogleSheetSyncHelper();
        $sheet_entries      = $googleSheetHelper->batchUpdateTaskentries($fetchUsersTaskTime, $this->google_sheet_id);
        Log::info('Batch Update Task Entries in google sheet hitted.');

        // Todo : Update ClickUp Task Time entries
        Log::info('Timedoctor Cron Ends');

    }

    /* Fetch Timedoctor Users Task Time */
    public function fetchUsersTaskTime(){
        Log::info('Fetch Users Task Time functions Starts');

        $getTimedoctorUsers =  $this->timedoctorService->getTimedoctorUsers();

        return array_reduce($getTimedoctorUsers ?? [], function ($carry, $user) {	
		
			$user_id   = $user['td_user_id']   ?? null;
			$user_name = $user['td_user_name'] ?? null;

			if ($user_id) {
				$user_stats = $this->getTasksStatstotal($user_id);

				$tracked_tasks = !empty($user_stats['data'][0]) 
					? $this->userTasktimeTracked($user_stats) 
					: [];

				$carry[$user_id] = [
					'id'             => $user['id'],
					'user_id'        => $user_id,
					'user_name'      => $user_name,
					'user_job_title' => $user['td_job_title'] ?? null,
					'tasks'          => []
				];

				foreach ($tracked_tasks as $task) {
					$task_id = $task['task_id'];

					if ($task_id) {
						$task_details = $this->getUserstaskDetails($task_id);

						$carry[$user_id]['tasks'][] = [
							'task_id'      => $task_details['task_id'],
							'task_name'    => $task_details['task_name'],
							'project_id'   => $task_details['project_id'],
							'project_name' => $task_details['project_name'],
							'time_tracked' => $task['time_tracked'],
							'user_email'   => $user['email']
						];
					}
				}
			}
			return $carry;
		}, []);
    }
    /* Fetch Timedoctor Users Task Time */

    public function getTasksStatstotal($user_id) {
		$company_id = $this->companyId;

		$from       = Carbon::yesterday()->startOfDay()->toIso8601String(); 
		$to 		= Carbon::yesterday()->endOfDay()->toIso8601String();  

        $timedoctorHelper = new TimedoctorApiHelper();
		$time_stats = $timedoctorHelper->get_tasks_stats_total($company_id, $user_id, $from, $to);
		return $time_stats;
	}


    public function userTasktimeTracked(array $user_stats): array {
		$task_times = [];

		if (!isset($user_stats['data'][0]) || !is_array($user_stats['data'][0])) {
            Log::info('User Stats is empty.');
			return [];
		}

		foreach ($user_stats['data'][0] as $user_stat) {
			if (!empty($user_stat) && isset($user_stat['time'], $user_stat['taskId'], $user_stat['userId'])) {  
				$task_Id = $user_stat['taskId'];
				$user_Id = $user_stat['userId'];
	
				if (!isset($task_times[$task_Id])) {
					$task_times[$task_Id] = [
						'task_id'      => $task_Id,
						'user_id'      => $user_Id,
						'time_tracked' => 0
					];
				}

				$task_times[$task_Id]['time_tracked'] += $user_stat['time'];
			}
		}

		// foreach ($task_times as $task) {
		// 	$task['time_tracked'] = sprintf('%02d:%02d', floor($task['time_tracked'] / 60), $task['time_tracked'] % 60);
        //     dd($task['time_tracked']);

		// }

		return array_values($task_times);
	}


    public function getUserstaskDetails($task_id) {
        $timedoctorHelper = new TimedoctorApiHelper();
        $tasks = $timedoctorHelper->get_timedoctor_user_tasks($this->companyId, $task_id);

        foreach ($tasks as $task) {
            $task_project     = $task['project'];
            $task_name        = $task['name'];
            $task_id	      = $task['id'];
            $project_id       = $task_project['id'];
            $project_details  = $timedoctorHelper->get_timedoctor_user_projects($this->companyId, $project_id);
            $project_name     = $project_details['data']['name'];	
        } 

        return [
            'task_id' 	   => $task_id,
            'task_name'    => $task_name,
            'project_id'   => $project_id,
            'project_name' => $project_name
        ];
    }
}
