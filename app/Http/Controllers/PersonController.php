<?php

namespace App\Http\Controllers;

use App\Faculty;
use App\Http\Requests\PointRequest;
use App\Http\Requests\RequestPerson;

use App\Point;
use App\Repositories\Person\PersonRepositoryInterface;
use App\Repositories\Faculty\FacultyRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Subject\SubjectRepositoryInterface;
use App\Repositories\Point\PointRepositoryInterface;
use App\Subject;
use App\Person;
use Validator;
use Illuminate\Http\Request;
use Response;
use Illuminate\Support\Facades\App;


class PersonController extends Controller
{
    protected $personRepository;

    public function __construct(
        PersonRepositoryInterface $personRepository,
        FacultyRepositoryInterface $facultyRepository,
        SubjectRepositoryInterface $subjectRepository,
        PointRepositoryInterface $pointRepository,
        UserRepositoryInterface $userRepository
    )
    {
        $this->personRepository = $personRepository;
        $this->facultyRepository = $facultyRepository;
        $this->subjectRepository = $subjectRepository;
        $this->pointRepository = $pointRepository;
        $this->userRepository = $userRepository;
        $this->middleware('auth');
        $this->middleware('permission:list|create|edit|delete',['only' => ['index','show']]);
        $this->middleware('permission:create',['only' => ['create','store']]);
        $this->middleware('permission:edit',['only' => ['update','edit']]);
        $this->middleware('permission:delete',['only' => 'delete']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        \request()->flash();
        $search = \request('search');
        $subject_count = Subject::all()->count();
        $students = $this->personRepository->search(request()->all(), $subject_count);
        $fas = $students->load('faculty')->all();
        $faculties = Faculty::all();
        return view('admin.persons.index', compact('students','a','fas','items', 'search', 'persons', 'faculties'));
    }

    /*
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $faculties = $this->facultyRepository->getAllList();
        $subjects = $this->subjectRepository->getAllList();
        $users = $this->userRepository->getAllList();
        return view('admin.persons.create', compact('faculties', 'users','subjects'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(RequestPerson $request)
    {
        $data = $request->all();
        $data['image'] = $this->personRepository->uploadImages();
        $data['slug'] = str_slug($data['name']);
        $post = $this->personRepository->create($data);
        return redirect()->to('en/person', compact('post'))->with('success', 'Create person success!');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $subjects = $this->subjectRepository->getAllList();
        $faculties = Faculty::all(['name', 'id']);
        $person = Person::where('slug', $slug)->firstOrFail();
        if (!empty($person->subjects)) {
            $datas = $person->subjects;
            foreach ($datas as $data) {
                $items[] = $data->pivot;
            }
        }
        $subject_points = $subjects->diff($datas);
        response()->json(array('subjects' => $subjects, 'datas' => $datas, 'subject_points' => $subject_points, 'person' => $person, 'faculties' => $faculties));
        return view('admin.persons.show', compact('person', 'subjects', 'items', 'faculties', 'subject_points', 'datas'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $where = array('id' => $id);
        $student = Person::where($where)->first();
        return Response::json($student);
    }

    public function getEditPerson($id){
        $faculties = $this->facultyRepository->getAllList();
        $subjects = $this->subjectRepository->getAllList();
        $person = $this->personRepository->getListById($id)->load('user');
        return view('admin.persons.update', compact('person', 'faculties', 'subjects'))->render();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    //ajax

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:persons,id,' . $id,
            'address' => 'required|max:255',
            'phone' => 'required|regex:/(0)[0-9]{9}/',
            'faculty_id' => 'required',
            'date' => 'required|before:today',
            'gender' => 'required',
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()->all()]);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->personRepository->uploadImages();
        }
        $data['slug'] = str_slug($data['name']);
        $person = $this->personRepository->update($id, $data);
        return Response::json([
            'person' => $person,
            'success' => 'Update success',
        ]);
    }

    public function updateEditPerson(RequestPerson $request, $id){
        $data = $request->all();
        if ($request->hasFile('image')) {
            $data['image'] = $this->personRepository->uploadImages();
        }
        $data['slug'] = str_slug($data['name']);
        $this->personRepository->update($id, $data);
        return redirect()->to('en/person')->with('success', 'Update person success!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->personRepository->delete($id);
        return redirect()->back()->with('success', 'Delete person success!');
    }

    public function createOrUpdate(PointRequest $request, $id)
    {
        $data_object = collect($request);
        if(count($data_object) == 2){
            return redirect()->back()->with('error','No subjects');
        }else{
            $x = 0;
            $subject_id = collect($request['subject_id']);
            $point = collect($request['point']);
            if (auth()->user()->admin == 0) {
                foreach ($point as $key => $item) {
                    $result[]['point'] = 0;
                }
            } else {
                foreach ($point as $key => $item) {
                    $result[] = $item;
                }
            }
            $data = array_slice($result, $x);
            if (count($subject_id) == count($data)) {
                foreach ($subject_id as $key => $value) {
                    $combined[$value] = $data[$x];
                    $x++;
                }
            }
            $person = Person::find($id)->load('subjects');
            $person->subjects()->syncWithoutDetaching($combined);
            return redirect()->back()->with('success', 'Create point success');
        }
    }

    public function deletePoint($person_id, $subject_id)
    {
        $person = Person::findOrFail($person_id);
        $person->subjects()->detach($subject_id);
        return redirect()->back()->with('success', 'Delete success!');
    }
}
//https://www.itsolutionstuff.com/post/laravel-53-how-to-create-seo-friendly-sluggable-urlexample.html