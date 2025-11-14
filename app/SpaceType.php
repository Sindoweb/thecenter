<?php

namespace App;

enum SpaceType: string
{
    case ConferenceRoom = 'conference_room';
    case Accommodation = 'accommodation';
    case CoWorking = 'co_working';
    case TherapyRoom = 'therapy_room';
    case Combined = 'combined';
}
