<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>October 2024 Calendar</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            width: 14.28%;
            border: 1px solid black;
        }
        th {
            height: 30px;
            vertical-align: center;
            background-color: #f4f4f4;
        }
        td {
            height: 100px;
            padding: 20px 0;
            position: relative;
            vertical-align: top;
        }
        .date {
            top: 5px;
            left: 5px;
            font-size: 20px;
            position: absolute;
            font-weight: 900;
        }
    </style>
</head>
<body>
    <h1>October 2024 Calendar</h1>
    <table>
        <tr>
            <th>Sun</th>
            <th>Mon</th>
            <th>Tue</th>
            <th>Wed</th>
            <th>Thu</th>
            <th>Fri</th>
            <th>Sat</th>
        </tr>
        @php
            $date = \Carbon\Carbon::create(2024, 10, 1);
            $daysInMonth = $date->daysInMonth;
            $firstDayOfMonth = $date->dayOfWeek; // 0 = Sun, 6 = Sat
            $day = 1;
                
            // Initialize the week sales assignments
            $currentWeek = 0;
            $salesDayIndex = 0;
        @endphp
        <tr>
            @while ($day <= $daysInMonth)
                @for ($i = 0; $i < 7; $i++)
                    <!-- fill empty cells before 1st day and after last day -->
                    @if (($i < $firstDayOfMonth && $day == 1) || $day > $daysInMonth)
                        <td></td>
                    @else
                        <td>
                            <span class="date">{{ $day++ }}</span>
                            @if($currentWeek < 4 && $i>0)
                                <ul>
                                    @foreach($salesSchedule[$currentWeek] as $salesRep => $days)
                                        <li><strong>Sales {{ $salesRep+1 }}</strong></li>
                                        <ul>
                                            @if (isset($days[$salesDayIndex])) 
                                                @foreach ($days[$salesDayIndex] as $store)
                                                    <li>{{ $store['name'] }}</li>
                                                @endforeach
                                            @endif
                                        </ul>
                                    @endforeach
                                </ul>
                                @php
                                    $salesDayIndex = ($salesDayIndex + 1) % 6;
                                    if ($salesDayIndex == 0) {
                                        $currentWeek++;
                                    }
                                @endphp
                            @endif
                        </td>
                    @endif
                    <!-- close and open tag for a new week -->
                    @if (($firstDayOfMonth + $day - 1) % 7 == 0)
                        </tr>
                        @if ($day <= $daysInMonth)
                            <tr>
                        @endif
                    @endif
                @endfor
            @endwhile
    </table>
</body>
</html>
