<?php namespace DreamFactory\Core\GraphQL\Http\Controllers;

if (class_exists('Illuminate\Routing\Controller')) {
    
    class Controller extends \Illuminate\Routing\Controller
    {
        
    }
    
} elseif (class_exists('Laravel\Lumen\Routing\Controller')) {
    
    class Controller extends \Laravel\Lumen\Routing\Controller
    {
        
    }
    
} else {
    
    class Controller
    {
        
    }
    
}
