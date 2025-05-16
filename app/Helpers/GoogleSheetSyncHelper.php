<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Services\TimedoctorService;
use Google_Client;
use Google_Service_Sheets;
use Carbon\Carbon;

class GoogleSheetSyncHelper
{
    public function google_service_sheet() {
		try {
            $timedoctorService = new TimedoctorService();
            $getTimeDoctorDetail = $timedoctorService->getTimeDoctor();
			$credentials = $getTimeDoctorDetail->google_service_credentials;
			$google_service_credentials = json_decode($credentials, true);
			$client = new \Google_Client();
			$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
			$client->setAuthConfig($google_service_credentials);
			return new \Google_Service_Sheets($client);
		}
		catch(Exception $e){
            Log::error('Google Service Sheet Error: ' . $e->getMessage());
			return null;
		}
	}

    public function batchUpdateTaskentries($data, $google_sheet_id) {
		try {
            Log::info('Batch Update Task Entries in google sheet started.');

			$spread_sheet_Id  = $google_sheet_id;
			$all_entries      = [];
			$starting_row     = 2; // Row where data starts (Skip row 1 for header)
			$column_start     = 'C';
			$column_end       = 'L';
			$sheet_entries    = [];
	
			$service   = $this->google_service_sheet();
			$row_Index = $starting_row;
	
			// Define header row (this will only be inserted once)
			$header = [
				'User ID', 
				'User Email', 
				'User Name', 
				'User Job Title',
				'Project ID', 
				'Project Name', 
				'Task ID', 
				'Task Name', 
				'Time Tracked',
				'Date'
			];
	
			$this->deleteTodaysRecords("Sheet1!{$column_start}2:{$column_end}", $service, $spread_sheet_Id, 0);

			$sheet_data    = $service->spreadsheets_values->get($spread_sheet_Id, "Sheet1!{$column_start}1:{$column_end}");
            $existing_rows = count($sheet_data->getValues() ?? []);
	
			if ($existing_rows <= 1) {
				$headerRange   = "Sheet1!{$column_start}1:{$column_end}1";
				$all_entries[] = new \Google_Service_Sheets_ValueRange([
					'range'  => $headerRange,
					'values' => [$header]
				]);
			} else {
				$row_Index = $existing_rows + 1;
			}

			$existing_sheets = $service->spreadsheets->get($spread_sheet_Id)->getSheets();
			$sheet_exists    = false;
			$sheet_id = 0;
			foreach ($existing_sheets as $sheet) {
				if ($sheet['properties']['title'] === "Sheet1") {
					$sheet_id = $sheet['properties']['sheetId'];
					break;
				}
			}

			$total_task_count = 0;
			foreach ($data as $user_id => $user_data) {
				$total_task_count += count($user_data['tasks']);
			}

			if ($row_Index > 2) {
				$request_body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
					'requests' => [
						new \Google_Service_Sheets_Request([
							'insertDimension' => new \Google_Service_Sheets_InsertDimensionRequest([
								'range' => [
									'sheetId'    => 0,
									'dimension'  => 'ROWS',
									'startIndex' => $row_Index - 1,
									'endIndex'   => $row_Index + $total_task_count,
								],
								"inheritFromBefore" => false
							]),
						])
					]
				]);
	
				$service->spreadsheets->batchUpdate($spread_sheet_Id, $request_body);
			}

			foreach ($data as $user_id => $user_data) {
				if (!isset($user_data['tasks']) || empty($user_data['tasks'])) {
					continue;
				}
					
				if (stripos($user_data['user_job_title'], 'designer') === false) {
					$total_task_count += count($user_data['tasks']);

					foreach ($user_data['tasks'] as $task) {
						$range         = "Sheet1!{$column_start}{$row_Index}:{$column_end}{$row_Index}";
						$all_entries[] = new \Google_Service_Sheets_ValueRange([
							'range'  => $range,
							'values' => [[
								$user_data['user_id'] ?? "",
								$task['user_email'] ?? "",
								$user_data['user_name'] ?? "",
								$user_data['user_job_title'] ?? "",
								$task['project_id'] ?? "",
								$task['project_name'] ?? "",
								$task['task_id'] ?? "",
								$task['task_name'] ?? "",
								$task['time_tracked'] ?? "",
								date('Y-m-d')
							]]
						]);
	
						$sheet_entries [] = [
							'user_id'         => $user_data['user_id'] ?? "",
							'user_email'      => $task['user_email'] ?? "",
							'user_name'       => $user_data['user_name'] ?? "",
							'user_job_title'  => $user_data['user_job_title'] ?? "",
							'project_id'      => $task['project_id'] ?? "",
							'project_name'    => $task['project_name'] ?? "",
							'task_id'         => $task['task_id'] ?? "",
							'task_name'       => $task['task_name'] ?? "",
							'time_tracked'    => $task['time_tracked'] ?? ""
						];
	
						$row_Index++;
					}

					// We add these blank rows because when we have to add rows (as google sheet has limit of 1000 rows) in google sheet then it add rows from the last row and make that row empty
					// So to avoid this conflict we add an extra row in the google sheet
					$all_entries[] = new \Google_Service_Sheets_ValueRange([
						'range'  => "Sheet1!{$column_start}{$row_Index}:{$column_end}{$row_Index}",
						'values' => [[
							"",
							"",
							"",
							"",
							"",
							"",
							"",
							"",
							"",
							""
						]]
					]);
				} else {
					$designer_sheet_name = $user_data['user_name'] . "-" . $user_data['id'];
					$existing_sheets = $service->spreadsheets->get($spread_sheet_Id)->getSheets();
					$sheet_exists    = false;

					foreach ($existing_sheets as $sheet) {
						if ($sheet['properties']['title'] === $designer_sheet_name) {
							$sheet_exists = true;
							$sheet_id = $sheet['properties']['sheetId'];
							break;
						}
					}
	
					if (!$sheet_exists) {
						$requests = [
							new \Google_Service_Sheets_Request([
								'addSheet' => [
									'properties' => [
										'title' => $designer_sheet_name
									]
								]
							])
						];
						$batch_update_request = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
							'requests' => $requests
						]);

						$service->spreadsheets->batchUpdate($spread_sheet_Id, $batch_update_request);
					}

					$this->deleteTodaysRecords("{$designer_sheet_name}!{$column_start}2:{$column_end}", $service, $spread_sheet_Id, $sheet_id);
	
					$designer_data          = $service->spreadsheets_values->get($spread_sheet_Id, "{$designer_sheet_name}!{$column_start}1:{$column_end}");
                    $designer_existing_rows = count($designer_data->getValues() ?? []);
	
					$designer_row_Index = ($designer_existing_rows <= 1) ? 2 : $designer_existing_rows + 1; // If no data exists, start from row 2 (after header)
	
					if ($designer_row_Index > 2) {
						$request_body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
							'requests' => [
								new \Google_Service_Sheets_Request([
									'insertDimension' => new \Google_Service_Sheets_InsertDimensionRequest([
										'range' => [
											'sheetId'    => $sheet_id,
											'dimension'  => 'ROWS',
											'startIndex' => $designer_row_Index - 1,
											'endIndex'   => $designer_row_Index + $total_task_count,
										],
										'inheritFromBefore' => false,
									])
								])
							]
						]);
			
						$service->spreadsheets->batchUpdate($spread_sheet_Id, $request_body);
					}

					if ($designer_existing_rows <= 1) {
						$headerRange   = "{$designer_sheet_name}!{$column_start}1:{$column_end}1";

						$all_entries[] = new \Google_Service_Sheets_ValueRange([
							'range'  => $headerRange,
							'values' => [$header]
						]);
					}
	
					foreach ($user_data['tasks'] as $task) {
						$range         = "{$designer_sheet_name}!{$column_start}{$designer_row_Index}:{$column_end}{$designer_row_Index}";
						$all_entries[] = new \Google_Service_Sheets_ValueRange([
							'range'  => $range,
							'values' => [[
								$user_data['user_id'] ?? "",
								$task['user_email'] ?? "",
								$user_data['user_name'] ?? "",
								$user_data['user_job_title'] ?? "",
								$task['project_id'] ?? "",
								$task['project_name'] ?? "",
								$task['task_id'] ?? "",
								$task['task_name'] ?? "",
								$task['time_tracked'] ?? "",
								date('Y-m-d')
							]]
						]);
	
						$sheet_entries [] = [
							'user_id'         => $user_data['user_id'] ?? "",
							'user_email'      => $task['user_email'] ?? "",
							'user_name'       => $user_data['user_name'] ?? "",
							'user_job_title'  => $user_data['user_job_title'] ?? "",
							'project_id'      => $task['project_id'] ?? "",
							'project_name'    => $task['project_name'] ?? "",
							'task_id'         => $task['task_id'] ?? "",
							'task_name'       => $task['task_name'] ?? "",
							'time_tracked'    => $task['time_tracked'] ?? ""
						];
	
						$designer_row_Index++;
					}
					// We add these blank rows because when we have to add rows (as google sheet has limit of 1000 rows) in google sheet then it add rows from the last row and make that row empty
					// So to avoid this conflict we add an extra row in the google sheet
					$all_entries[] = new \Google_Service_Sheets_ValueRange([
						'range'  => "{$designer_sheet_name}!{$column_start}{$designer_row_Index}:{$column_end}{$designer_row_Index}",
						'values' => [[
							"",
							"",
							"",
							"",
							"",
							"",
							"",
							"",
							"",
							""
						]]
					]);
				}
			}
			
			if (!empty($all_entries)) {
				$body = new \Google_Service_Sheets_BatchUpdateValuesRequest([
					'valueInputOption' => 'RAW',
					'data' => $all_entries
				]);
	
				$service->spreadsheets_values->batchUpdate($spread_sheet_Id, $body);
			}

			return $sheet_entries;
		} catch (Exception $e) {
            Log::error('Exception error: ' . $e->getMessage());
			throw new Exception("Exception error: " . $e->getMessage());
		}
	}	


    private function deleteTodaysRecords($range, $service, $spread_sheet_Id, $sheet_id) {
		$data   = $service->spreadsheets_values->get($spread_sheet_Id, $range);
		$values = $data->getValues();
        if (empty($values)) {
            Log::info("No data found in range: $range");
            return;
        }
		$rows_to_delete = [];
	
		foreach ($values as $index => $row) {
			if (isset($row[9]) && Carbon::parse($row[9])->isToday()) {
				$rows_to_delete[] = $index + 2;
			}
		}
	
		if (!empty($rows_to_delete)) {
			rsort($rows_to_delete);
	
			$requests = [];
			foreach ($rows_to_delete as $row) {
				$requests[] = new \Google_Service_Sheets_Request([
					'deleteDimension' => [
						'range' => [
							'sheetId'    => $sheet_id,
							'dimension'  => 'ROWS',
							'startIndex' => $row - 1,
							'endIndex'   => $row
						]
					]
				]);
			}
	
			if (!empty($requests)) {
				$batch_update_request = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
					'requests' => $requests
				]);

				try {
					$service->spreadsheets->batchUpdate($spread_sheet_Id, $batch_update_request);
				} catch (Exception $e) {
                    Log::error('Error deleting rows: ' . $e->getMessage());
				}
				
			}
		} else {
            Log::error('No rows found for today to delete');
		}
	}
}