<?php
use App\Models\User;
use Spatie\Permission\Models\Role;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

$roles = ['company', 'job-seeker', 'expert', 'student', 'college', 'intern'];
foreach ($roles as $r) {
    Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
}

$users = User::all();
foreach ($users as $u) {
    if (strpos($u->email, 'company') !== false || strpos(strtolower($u->first_name), 'company') !== false) {
        if (!$u->hasRole('company', 'web')) {
            $u->assignRole(Role::where('name', 'company')->where('guard_name', 'web')->first());
            echo "Assigned company role to " . $u->email . "<br>";
        } else {
            echo $u->email . " already has company role<br>";
        }
    }
    
    if (strpos($u->email, 'seeker') !== false || strpos($u->email, 'aman') !== false) {
        if (!$u->hasRole('job-seeker', 'web')) {
            $u->assignRole(Role::where('name', 'job-seeker')->where('guard_name', 'web')->first());
            echo "Assigned job-seeker role to " . $u->email . "<br>";
        } else {
            echo $u->email . " already has job-seeker role<br>";
        }
    }
}
echo "Done checking roles.";
