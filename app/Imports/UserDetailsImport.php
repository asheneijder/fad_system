<?php

namespace App\Imports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserDetailsImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new User([
            'name'             => $row['displayname'] ?? null,
            'email'            => $row['mail'] ?? null,
            'email_verified_at' => Carbon::now(),
            'password'         => Hash::make('st@ff!@Rt!'),
            'remember_token'   => null,
            'graph_id'         => $row['id'] ?? null,
            'display_name'     => $row['displayname'] ?? null,
            'surname'          => $row['surname'] ?? null,
            'mail'             => $row['mail'] ?? null,
            'given_name'       => $row['givenname'] ?? null,
            'job_title'        => $row['jobtitle'] ?? null,
            'department'       => $row['department'] ?? null,
            'office_location'  => $row['companyname'] ?? null,
        ]);
    }
}
