<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use App\Models\Worktime;

class WorktimeRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i',
            'break_start'   => 'nullable|array',
            'break_start.*' => 'nullable|date_format:H:i',
            'break_end'     => 'nullable|array',
            'break_end.*'   => 'nullable|date_format:H:i',
            'remarks'       => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'remarks.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            // worktimeId はルートパラメータから取得
            $worktime = Worktime::find($this->route('worktimeId'));
            if (!$worktime) return;

            $date = $worktime->date->format('Y-m-d');

            $start = Carbon::parse("$date {$this->start_time}");
            $end   = Carbon::parse("$date {$this->end_time}");

            // 出勤 >= 退勤
            if ($start->gte($end)) {
                $validator->errors()->add('end_time', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 休憩チェック
            $breakStarts = (array)$this->break_start;
            $breakEnds   = (array)$this->break_end;

            foreach ($breakStarts as $i => $startValue) {
                $endValue = $breakEnds[$i] ?? null;

                if (!$startValue && !$endValue) continue;

                $breakStart = $startValue ? Carbon::parse("$date $startValue") : null;
                $breakEnd   = $endValue ? Carbon::parse("$date $endValue") : null;

                if ($breakStart && $breakStart->lt($start)) {
                    $validator->errors()->add("break_start.$i", '休憩時間が不適切な値です');
                }

                if ($breakStart && $breakStart->gt($end)) {
                    $validator->errors()->add("break_start.$i", '休憩時間もしくは退勤時間が不適切な値です');
                }

                if ($breakEnd && $breakEnd->gt($end)) {
                    $validator->errors()->add("break_end.$i", '休憩時間もしくは退勤時間が不適切な値です');
                }

                if ($breakStart && $breakEnd && $breakStart->gte($breakEnd)) {
                    $validator->errors()->add("break_end.$i", '休憩時間が不適切な値です');
                }
            }
        });
    }
}
