@extends('layouts.client')

@section('title', trans('main.Rank') . ' | ' . trans('main.Contest') . $contest->id . ' | ' . get_setting('siteName'))

@section('content')

  <div class="container">
    <div class="row">
      {{-- 菜单 --}}
      <div class="col-sm-12 col-12">
        <x-contest.navbar :contest="$contest" :group-id="$_GET['group'] ?? null" />
      </div>
    </div>
  </div>

  <div class="container">
    <div class="row">
      <div class="col-sm-12 col-12">
        <div class="my-container bg-white">

          <h4 class="text-center">{{ $contest->id }}. {{ $contest->title }}</h4>
          <hr class="mt-0">

          <form id="form_rank" action="" method="get">

            @if (isset($_GET['group']))
              <input name="group" value="{{ $_GET['group'] }}" hidden>
            @endif

            {{-- 提交记录折线图 --}}
            <div>
              <x-solution.line-chart default-past="300i" :contest-id="$contest->id" />
            </div>

            <div class="float-left">
              <button class="btn btn-sm" onclick="down_rank()">{{ __('main.Download') }}</button>
            </div>
            @if ($contest->lock_rate > 0 && time() > $end_time) {{-- 封榜了 --}}
              <div class="float-left">
                <span class="btn btn-sm">
                  <i class="fa fa-exclamation-triangle" aria-hidden="true" style="color: red"></i>
                  {{ trans('sentence.rank_end_time', ['time' => date('Y-m-d H:i:s', $end_time)]) }}
                </span>
              </div>
            @endif

            {{-- 实时更新榜单的按钮 --}}
            <div class="pull-right">
              @if (strtotime($contest->end_time) < time() && ((Auth::check() && privilege('admin.contest')) || $contest->lock_rate == 0))
                <span title="{{ __('sentence.Up to now') }}">{{ trans('main.Up to now') }}：</span>
                <input id="switch_buti" type="checkbox">
                <input type="text" name="buti" onchange="this.form.submit()" value="{{ isset($_GET['buti']) ? $_GET['buti'] : 'false' }}" hidden>
              @endif
              <script>
                $(function() {
                  new Switch($("#switch_buti")[0], {
                    size: 'small',
                    checked: $('input[name=buti]').attr('value') === 'true',
                    onChange: function() {
                      $("input[name=buti]").attr('value', this.getChecked());
                      $("#form_rank").submit();
                    }
                  });
                })
              </script>
            </div>

            {{-- 榜单表格 --}}
            <div class="table-responsive">
              <table id="table_rank" class="table table-sm table-hover border-bottom">
                <thead>
                  <tr>
                    <th width="5%" class="text-center">{{ trans('main.Rank') }}</th>
                    <th width="5%"><input type="text" class="form-control" placeholder="{{ trans('main.Username') }}" style="height: auto;font-size: 0.9rem"
                        onchange="this.form.submit()" name="username" value="{{ isset($_GET['username']) ? $_GET['username'] : '' }}">
                    </th>
                    @if (get_setting('rank_show_school'))
                      <th width="5%" class="text-center">
                        <input type="text" class="form-control" placeholder="{{ trans('main.School') }}" style="height: auto;font-size: 0.9rem" onchange="this.form.submit()"
                          name="school" value="{{ isset($_GET['school']) ? $_GET['school'] : '' }}">
                      </th>
                    @endif
                    @if (get_setting('rank_show_class'))
                      <th width="5%" class="text-center">
                        <input type="text" class="form-control" placeholder="{{ trans('main.Class') }}" style="height: auto;font-size: 0.9rem" onchange="this.form.submit()"
                          name="class" value="{{ isset($_GET['class']) ? $_GET['class'] : '' }}">
                      </th>
                    @endif
                    @if (get_setting('rank_show_nick'))
                      <th width="5%" class="text-center">
                        <input type="text" class="form-control" placeholder="{{ trans('main.Name') }}" style="height: auto;font-size: 0.9rem" onchange="this.form.submit()"
                          name="nick" value="{{ isset($_GET['nick']) ? $_GET['nick'] : '' }}">
                      </th>
                    @endif
                    <th width="5%" class="text-center">{{ $contest->judge_type == 'acm' ? trans('main.Solved') : trans('main.Score') }}</th>
                    <th width="5%" class="text-center">{{ trans('main.Penalty') }}</th>
                    @for ($i = 0; $i < $problem_count; $i++)
                      <th class="text-center"><a href="{{ route('contest.problem', [$contest->id, $i, 'group' => $_GET['group'] ?? null]) }}">{{ index2ch($i) }}</a></th>
                    @endfor
                  </tr>
                </thead>
                <tbody>
                  @foreach ($users as $user)
                    <tr>
                      <td class="text-center" nowrap>
                        {{-- 排名 --}}
                        @if ($loop->first)
                          <font class="px-1" style="background-color: #fff959">
                            <i class="fa fa-thumbs-o-up pr-1" aria-hidden="true"></i>WIN
                          </font>
                        @elseif($loop->iteration <= count($users) * 0.1)
                          <font class="px-1" style="background-color: #fff95a">{{ $loop->iteration }}</font>
                        @elseif($loop->iteration <= count($users) * 0.3)
                          <font class="px-1" style="background-color: #e8e8e8">{{ $loop->iteration }}</font>
                        @elseif($loop->iteration <= count($users) * 0.6)
                          <font class="px-1" style="background-color: #f5ac00">{{ $loop->iteration }}</font>
                        @else
                          <font>{{ $loop->iteration }}</font>
                        @endif
                      </td>
                      <td nowrap><a href="{{ route('user', $user['username']) }}">{{ $user['username'] }}</a></td>
                      @if (get_setting('rank_show_school'))
                        <td nowrap>{{ $user['school'] }}</td>
                      @endif
                      @if (get_setting('rank_show_class'))
                        <td nowrap>{{ $user['class'] }}</td>
                      @endif
                      @if (get_setting('rank_show_nick'))
                        <td nowrap>{{ $user['nick'] }}</td>
                      @endif
                      <td class="text-center"
                        onclick="window.open('{{ route('contest.solutions', [$contest->id, 'username' => $user['username'], 'group' => $_GET['group'] ?? null]) }}','_blank')">
                        {{ $user['score'] }}</td>
                      <td class="text-center"
                        onclick="window.open('{{ route('contest.solutions', [$contest->id, 'username' => $user['username'], 'group' => $_GET['group'] ?? null]) }}','_blank')">
                        {{ $user['penalty'] }}</td>
                      {{-- 下面是每一道题的情况 --}}
                      @for ($i = 0; $i < $problem_count; $i++)
                        <td
                          @if (isset($user[$i])) @if ($user[$i]['AC'])
                                                        @if ($user[$i]['AC_time'] > $contest->end_time)
                                                            style="background-color: #99d7ff"
                                                        @elseif(isset($user[$i]['first_AC']))
                                                            style="background-color: #12d000"
                                                        @else
                                                            style="background-color: #87ec97" @endif
                        @else style="background-color: #ffafa7" @endif
                          onclick="window.open('{{ route('contest.solutions', [$contest->id, 'username' => $user['username'], 'index' => $i, 'group' => $_GET['group'] ?? null]) }}','_blank')"
                      @endif
                      class="border text-center">
                      {{ isset($user[$i]) ? $user[$i]['AC_info'] : null }}
                      </td>
                  @endfor
                  </tr>
                  @endforeach
                </tbody>
              </table>
              <button hidden></button>
            </div>
          </form>

          <div>
            <div><i class="fa fa-square" aria-hidden="true" style="color: #12d000"></i> {{ __('sentence.firstAC') }}</div>
            <div><i class="fa fa-square" aria-hidden="true" style="color: #87ec97"></i> {{ __('sentence.normalAC') }}</div>
            <div><i class="fa fa-square" aria-hidden="true" style="color: #ffafa7"></i> {{ __('sentence.normalWA') }}</div>
            <div><i class="fa fa-square-o" aria-hidden="true"></i> {{ __('sentence.noSubmit') }}</div>
            <div><i class="fa fa-square" aria-hidden="true" style="color: #99d7ff"></i> {{ __('sentence.endedAC') }}</div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <script type="text/javascript">
    // 下载表格
    function down_rank() {
      $("#table_rank").table2excel({
        name: "rank",
        // Excel文件的名称
        filename: "Rank-Contest{{ $contest->id }}-{{ $contest->title }}"
      });
    }
  </script>
@endsection
