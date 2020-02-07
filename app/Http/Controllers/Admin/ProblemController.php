<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProblemController extends Controller
{
    //管理员显示题目列表
    public function problems(){
        $problems=DB::table('problems')->select('id','title','source','spj','created_at','hidden',
            DB::raw("(select count(id) from solutions where problem_id=problems.id) as submit"),
            DB::raw("(select count(id) from solutions where problem_id=problems.id and result=4) as  solved")
            )->orderBy('id')->paginate(100);
        return view('admin.problem.list',compact('problems'));
    }

    //管理员添加题目
    public function add_problem(Request $request){
        //提供加题界面
        if($request->isMethod('get')){
            $pageTitle='添加题目 - 程序设计';
            return view('admin.problem.edit',compact('pageTitle'));
        }
        //提交一条新数据
        if($request->isMethod('post')){
            $problem=$request->input('problem');
            unset($problem['id']);
            $id=DB::table('problems')->insertGetId($problem);
            save_problem_samples($id,(array)$request->input('samples'));//保存样例
            $msg=sprintf('题目<a href="%s" target="_blank">%d</a>添加成功',route('problem',$id),$id);
            return view('admin.success',compact('msg'));
        }
    }

    //管理员修改题目
    public function update_problem(Request $request,$id=-1)
    {
        //get提供修改界面
        if ($request->isMethod('get')) {

            $pageTitle='修改题目 - 程序设计';
            if($id==-1) {
                if(isset($_GET['id']))//用户手动输入了题号
                    return redirect(route('admin.update_problem_withId',$_GET['id']));
                return view('admin.edit',compact('pageTitle'))->with('lack_id',true);
            } //询问要修改的题号
            $problem=DB::table('problems')->find($id);
            if($problem==null)
                return view('admin.fail',['msg'=>'该题目不存在或操作有误!']);

            $samples=read_problem_samples($problem->id);

            //看看有没有特判文件
            $spjPath = base_path('storage/data/'.$problem->id.'/spj/spj.cpp');
            $hasSpj=file_exists($spjPath);

            return view('admin.problem.edit',compact('pageTitle','problem','samples','hasSpj'));
        }

        // 提交修改好的题目数据
        if($request->isMethod('post')){
            $problem=$request->input('problem');
            if(!isset($problem['spj']))
                $problem['spj']=0;
            $samples=$request->input('samples');
            $spjFile=$request->file('spj_file');

            save_problem_samples($problem['id'],(array)$samples);
            if($spjFile!=null && $spjFile->isValid())
                save_problem_spj_code($problem['id'],$spjFile);

            DB::table('problems')->where('id',$problem['id'])->update($problem);
            $msg=sprintf('题目<a href="%s" target="_blank">%d</a>修改成功',route('problem',$problem['id']),$problem['id']);
            return view('admin.success',['msg'=>$msg]);
        }
    }

    //管理员修改题目状态  0密封 or 1公开
    public function change_hidden_to(Request $request){
        if($request->ajax()){
            $pids=$request->input('pids')?:[];
            $hidden=$request->input('hidden');
            return DB::table('problems')->whereIn('id',$pids)->update(['hidden'=>$hidden]);
        }
        return 0;
    }

    //重判题目|竞赛|提交记录
    public function rejudge(Request $request){

        if($request->isMethod('get')){
            $pageTitle='重判';
            return view('admin.problem.rejudge',compact('pageTitle'));
        }

        if($request->isMethod('post')){
            $pid=$request->input('pid');
            $cid=$request->input('cid');
            $sid=$request->input('sid');

            $count=DB::table('solutions')
                ->when($pid,function ($q)use($pid){$q->orWhere('problem_id',$pid);})
                ->when($cid&&$pid>0,function ($q)use($cid){$q->orWhere('contest_id',$cid);})
                ->when($sid,function ($q)use($sid){$q->orWhere('id',$sid);})
                ->update(['result'=>0]);

            return view('admin.success',['msg'=>sprintf('已重判%d条提交记录，可前往状态查看',$count)]);
        }
    }

}
