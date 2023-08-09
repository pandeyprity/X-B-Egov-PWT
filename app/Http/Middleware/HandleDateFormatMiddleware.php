<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HandleDateFormatMiddleware
{
    private $_REQUEST;
    private $_requestCollection;
    private $_reqs;
    private $_clientFormat;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $this->_reqs = $request;
        $this->_REQUEST = $request->all();
        $this->_requestCollection = collect($request->all());

        if ($request->hasHeader('Date-Format')) {
            $this->_clientFormat = $request->header('Date-Format');
            $this->changeDateFormat();
        }
        return $next($request);
    }

    /**
     * 
     */
    public function changeDateFormat()
    {
        foreach ($this->_REQUEST as $req => $value) {
            if (!is_array($value)) {
                if ($this->isStringDate($value)) {
                    $this->isValidDate($value, $req);
                    $parsedDate = Carbon::createFromFormat($this->_clientFormat, $value)
                        ->format('Y-m-d');
                    $this->_REQUEST[$req] = $parsedDate;
                    $this->_reqs->merge([$req => $parsedDate]);
                }
            }

            // If The Request contains nested
            if (is_array($value)) {
                foreach ($value as $b => $val) {
                    if (is_array($val)) {
                        foreach ($val as $field => $a) {
                            if ($this->isStringDate($a)) {
                                $parsedDate = Carbon::createFromFormat($this->_clientFormat, $a)
                                    ->format('Y-m-d');
                                $this->_REQUEST[$req][$b][$field] = $parsedDate;
                            }
                        }
                    } else {                                // If Object            
                        if ($this->isStringDate($val)) {
                            $parsedDate = Carbon::createFromFormat($this->_clientFormat, $val)
                                ->format('Y-m-d');
                            $this->_REQUEST[$req][$b] = $parsedDate;
                        }
                    }
                }
            }
            // Final Generated Request
            $this->_reqs->merge($this->_REQUEST);
        }
    }

    /**
     * | Check Date Format of Date
     */
    public function isStringDate($value)
    {
        $data = strtotime($value);
        return $data;
    }

    /**
     * | 
     */
    function isValidDate($date, $field)
    {
        $format = $this->_clientFormat;
        $dateTime = DateTime::createFromFormat($format, $date);
        if ($dateTime && $dateTime->format($format) === $date) {
        } else {
            abort(response()->json(
                [
                    'status' => false,
                    'message' => "The Given Date Format is not matched with your specified date format for $field",
                    'data' => []
                ]
            ));
        }
    }
}
