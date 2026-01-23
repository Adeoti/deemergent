<?php

namespace App\Models;

use App\Models\User;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Homework;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    //


    protected $table = "classes";
    public function homeworks()
    {
        return $this->hasMany(Homework::class);
    }

    // App\Models\SchoolClass.php

    public function students()
    {
        return $this->hasMany(Student::class);
    }

   

    protected $fillable = [
        'name',
        // 'class_teacher_id',
        'branch_ids',
        'capacity',
    ];

    protected $casts = [
        'branch_ids' => 'array',
    ];

    // Relationships (optional, based on your setup)
    public function user()
    {
        return $this->belongsTo(User::class, 'class_teacher_id');
    }

    public function studentclass(){
        return $this->hasOne(User::class, 'student_class');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
