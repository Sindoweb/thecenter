<?php

namespace App;

enum DurationType: string
{
    case HalfDay = 'halve_dag';
    case FullDay = 'hele_dag';
    case Night = 'nacht';
    case Session = 'sessie';
    case DayPass = 'dagpas';
    case Weekly = 'wekelijks';
    case Monthly = 'maandelijks';
    case Quarterly = 'kwartaal';
}
