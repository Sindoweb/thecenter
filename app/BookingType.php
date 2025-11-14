<?php

namespace App;

enum BookingType: string
{
    case Conferentie = 'conferentie';
    case Accommodation = 'accommodation';
    case CoWorking = 'co_working';
    case LightTherapy = 'light_therapy';
    case Package = 'package';
}
