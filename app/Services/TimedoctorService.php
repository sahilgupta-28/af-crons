<?php

namespace App\Services;

use App\Models\Timedoctor;
use Illuminate\Support\Facades\Storage;
use App\Models\TimedoctorUser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Helpers\TimedoctorApiHelper;
use Illuminate\Support\Facades\Log;

class TimedoctorService
{

    /**
     * Save the form data to the database.
     *
     * @param array $data
     * @return Timedoctor
     */
    public function saveTimedoctorData(array $data)
    {
        $getTimedoctor = $this->getTimeDoctor();
        $getTimedoctor == null ? Timedoctor::create($data) : $getTimedoctor->update($data);
    }
    
    /**
     * Retrieve the latest Timedoctor settings from the database.
     *
     * @return Timedoctor|null
     */
    public function getTimeDoctor(){
        return Timedoctor::latest()->first();
    }

     /**
     * Retrieve the Timedoctor users from the database.
     *
     * @return TimedoctorUser
     */
    public function getTimedoctorUsers(){
        return TimedoctorUser::all()->toArray();
    }


    /* Save Timedoctor Users */
    public function saveTimedoctorUsers($companyId)
    {
        try {
            $timedoctorHelper = new TimedoctorApiHelper();
            $nextUrl = null;
    
            do {
                $timedoctorUsers = $timedoctorHelper->getApiTimedoctorUsers($companyId, $nextUrl);
                Log::info('Fetched Time Doctor users.');
    
                $this->insertTimedoctorUsers($timedoctorUsers);
    
                // Set next URL for the next loop
                $nextUrl = $timedoctorUsers['paging']['next'] ?? null;
    
            } while ($nextUrl !== null);
    
            Log::info('All Time Doctor users have been saved.');
    
        } catch (\Exception $e) {
            Log::error('Failed to save Time Doctor users: ' . $e->getMessage());
        }
    }
    /* Save Timedoctor Users */

    /* Save Timedoctor Users in DB*/
    public function insertTimedoctorUsers($data)
    {
        try {
            $users = $data['data'] ?? [];
    
            if (empty($users)) {
                Log::info('No users found from Timedoctor API.');
                return;
            }
    
            $userIds = array_column($users, 'id');
    
            $existingUserIds = [];
            $chunks = array_chunk($userIds, 100);
            foreach ($chunks as $chunk) {
                $existingUserIds = array_merge($existingUserIds,
                    TimedoctorUser::whereIn('td_user_id', $chunk)
                        ->pluck('td_user_id')
                        ->toArray()
                );
            }
    
            $insertData = [];
            $now = now(); // better than calling date() multiple times
    
            foreach ($users as $user) {
                if (!in_array($user['id'], $existingUserIds)) {
                    $insertData[] = [
                        'td_user_id'        => $user['id'] ?? null,
                        'td_user_name'      => $user['name'] ?? '',
                        'td_job_title'      => $user['jobTitle'] ?? '',
                        'email'             => $user['email'] ?? '',
                        'profile_time_zone' => $user['profileTimezone'] ?? '',
                        'created_at'        => $now,
                        'updated_at'        => $now
                    ];
                }
            }
    
            // Insert in chunks to avoid memory or SQL limits
            foreach (array_chunk($insertData, 100) as $chunk) {
                TimedoctorUser::insert($chunk);
            }
    
        } catch (\Exception $e) {
            Log::error('Failed to insert Time Doctor users: ' . $e->getMessage());
        }
    }
    /* Save Timedoctor Users in DB*/



}
