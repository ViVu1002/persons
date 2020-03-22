<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Repositories\Person\PersonRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function __construct(PersonRepositoryInterface $personRepository)
    {
        $this->personRepository = $personRepository;
    }

    public function test(){
        $users = $this->personRepository->getPoint();
        foreach ($users as $user){
            $job = (new SendEmailJob($user));
            dispatch($job);
        }
        return redirect()->back()->with('success','Send email success');
    }
}
