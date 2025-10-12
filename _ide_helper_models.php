<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $check_in_time
 * @property \Illuminate\Support\Carbon|null $check_out_time
 * @property string|null $photo_path
 * @property string|null $check_in_location
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereCheckInLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereCheckInTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereCheckOutTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereUpdatedAt($value)
 */
	class Attendance extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $employee_id
 * @property string $date
 * @property string $type
 * @property \Illuminate\Support\Carbon $submission_time
 * @property string|null $reason
 * @property string $status
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\User|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest whereSubmissionTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRequest whereUpdatedAt($value)
 */
	class AttendanceRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $nik
 * @property string $email
 * @property string $gender
 * @property string $departement
 * @property string $position
 * @property string|null $title
 * @property string|null $photo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payroll> $payrolls
 * @property-read int|null $payrolls_count
 * @property-read \App\Models\Recruitment|null $recruitment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tunjangan> $tunjangan
 * @property-read int|null $tunjangan_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDepartement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNik($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUserId($value)
 */
	class Employee extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $type
 * @property string|null $reason
 * @property string $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\Employee $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereUpdatedAt($value)
 */
	class LeaveRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property string|null $reason
 * @property string $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\Employee $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRequest whereUpdatedAt($value)
 */
	class OvertimeRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $employee_id
 * @property string $basic_salary
 * @property string $bonus
 * @property string $deductions
 * @property string $net_salary
 * @property string $month
 * @property string $year
 * @property int $generated_by
 * @property string $generated_at
 * @property string|null $slip_file
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\User $generator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereBasicSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereDeductions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereGeneratedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereGeneratedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereNetSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereSlipFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereYear($value)
 */
	class Payroll extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $employee_id
 * @property int|null $payroll_id
 * @property string $nama
 * @property string $jumlah
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\Payroll|null $payroll
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Potongan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Potongan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Potongan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Potongan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Potongan whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Potongan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Potongan whereJumlah($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Potongan whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Potongan wherePayrollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Potongan whereUpdatedAt($value)
 */
	class Potongan extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $employee_id
 * @property string $address
 * @property string $place_of_birth
 * @property string $date_of_birth
 * @property string $kk_number
 * @property string $religion
 * @property string $gender
 * @property string $departement
 * @property string $contract_end_date
 * @property string $email
 * @property string $password
 * @property string $phone
 * @property string $marital_status
 * @property string $education
 * @property string $tmt
 * @property string $salary
 * @property string|null $photo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereContractEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereDateOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereDepartement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereEducation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereKkNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereMaritalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment wherePlaceOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereReligion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereTmt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recruitment whereUserId($value)
 */
	class Recruitment extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $employee_id
 * @property string $nama
 * @property string $jumlah
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tunjangan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tunjangan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tunjangan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tunjangan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tunjangan whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tunjangan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tunjangan whereJumlah($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tunjangan whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tunjangan whereUpdatedAt($value)
 */
	class Tunjangan extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property string|null $photo
 * @property string $role
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

