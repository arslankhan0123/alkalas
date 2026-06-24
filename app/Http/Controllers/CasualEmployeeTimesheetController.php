<?php

namespace App\Http\Controllers;

use App\Http\Requests\CasualEmployeeTimesheetRequest;
use App\Models\CasualEmployee;
use App\Models\HourlyTimesheet;
use App\Models\MonthlyTimesheet;
use App\Queries\CasualEmployeeTimesheetDataTable;
use App\Repositories\CasualEmployeeTimesheetRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CasualEmployeeTimesheetController extends Controller
{
    private $casualEmployeeTimesheetRepository;

    public function __construct(CasualEmployeeTimesheetRepository $casualEmployeeTimesheetRepository)
    {
        $this->casualEmployeeTimesheetRepository = $casualEmployeeTimesheetRepository;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of((new CasualEmployeeTimesheetDataTable())->get($request->all()))->make(true);
        }

        // Get unique clients, projects, and months for the filter selects
        $clients = DB::table('hourly_timesheets')->select('client_name')->distinct()->pluck('client_name');
        $projects = DB::table('hourly_timesheets')->select('project_name')->distinct()->pluck('project_name');


        return view('casual_employee_timesheets.index', compact('clients', 'projects'));
    }


    public function create()
    {
        $casualEmployees = CasualEmployee::all();
        return view('casual_employee_timesheets.create', compact('casualEmployees'));
    }

    public function store(CasualEmployeeTimesheetRequest $request)
    {
        $input = $request->all();

        // Generate a unique batch ID
        $batchId = 'BATCH_' . time() . '_' . uniqid();

        try {
            \DB::transaction(function () use ($input, $batchId) {
                // Save hourly timesheets
                if (!empty($input['hourly_entries'])) {
                    foreach ($input['hourly_entries'] as $hourlyEntry) {
                        $hourlyEntry['timesheet_date'] = $input['timesheet_date'];
                        $hourlyEntry['batch_id'] = $batchId;
                        $hourlyEntry['client_name'] = $input['client_name'] ?? null;
                        $hourlyEntry['project_name'] = $input['project_name'] ?? null;
                        $this->casualEmployeeTimesheetRepository->createHourlyTimesheet($hourlyEntry);
                    }
                }

                // Save monthly timesheets
                if (!empty($input['monthly_entries'])) {
                    foreach ($input['monthly_entries'] as $monthlyEntry) {
                        $monthlyEntry['month_year'] = $input['month_year'];
                        $monthlyEntry['batch_id'] = $batchId;
                        $hourlyEntry['client_name'] = $input['client_name'] ?? null;
                        $hourlyEntry['project_name'] = $input['project_name'] ?? null;
                        $this->casualEmployeeTimesheetRepository->createMonthlyTimesheet($monthlyEntry);
                    }
                }
            });

            return redirect()
                ->route('casual-employee-timesheets.index')
                ->with('success', 'Timesheet batch saved successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Error saving timesheet batch: ' . $e->getMessage()]);
        }
    }

    public function show($batchId)
    {
        // Get all timesheets for this batch
        $hourlyTimesheets = HourlyTimesheet::with('casualEmployee')
            ->where('batch_id', $batchId)
            ->get();

        $monthlyTimesheets = MonthlyTimesheet::with('casualEmployee')
            ->where('batch_id', $batchId)
            ->get();

        if ($hourlyTimesheets->isEmpty() && $monthlyTimesheets->isEmpty()) {
            abort(404, 'Timesheet batch not found');
        }

        $first = $hourlyTimesheets->first() ?? $monthlyTimesheets->first();

        $clientName = $first->client_name ?? 'N/A';
        $projectName = $first->project_name ?? 'N/A';

        return view('casual_employee_timesheets.show', compact(
            'hourlyTimesheets',
            'monthlyTimesheets',
            'batchId',
            'clientName',
            'projectName'
        ));
    }

    public function edit($batchId)
    {
        $casualEmployees = CasualEmployee::all();

        // Get all timesheets for this batch
        $hourlyTimesheets = HourlyTimesheet::with('casualEmployee')
            ->where('batch_id', $batchId)
            ->get();

        $monthlyTimesheets = MonthlyTimesheet::with('casualEmployee')
            ->where('batch_id', $batchId)
            ->get();

        if ($hourlyTimesheets->isEmpty() && $monthlyTimesheets->isEmpty()) {
            abort(404, 'Timesheet batch not found');
        }

        // Get the dates from the first record
        $timesheetDate = $hourlyTimesheets->isNotEmpty() ? $hourlyTimesheets->first()->timesheet_date : null;
        $monthYear = $monthlyTimesheets->isNotEmpty() ? $monthlyTimesheets->first()->month_year : null;

        return view('casual_employee_timesheets.edit', compact(
            'hourlyTimesheets',
            'monthlyTimesheets',
            'casualEmployees',
            'batchId',
            'timesheetDate',
            'monthYear'
        ));
    }

    // public function update(CasualEmployeeTimesheetRequest $request, $batchId)
    // {
    //     $input = $request->all();

    //     try {
    //         \DB::transaction(function () use ($input, $batchId) {
    //             // Delete existing entries for this batch
    //             HourlyTimesheet::where('batch_id', $batchId)->delete();
    //             MonthlyTimesheet::where('batch_id', $batchId)->delete();

    //             // Create new hourly timesheets
    //             if (!empty($input['hourly_entries'])) {
    //                 foreach ($input['hourly_entries'] as $hourlyEntry) {
    //                     $hourlyEntry['timesheet_date'] = $input['timesheet_date'];
    //                     $hourlyEntry['batch_id'] = $batchId;
    //                     $this->casualEmployeeTimesheetRepository->createHourlyTimesheet($hourlyEntry);
    //                 }
    //             }

    //             // Create new monthly timesheets
    //             if (!empty($input['monthly_entries'])) {
    //                 foreach ($input['monthly_entries'] as $monthlyEntry) {
    //                     $monthlyEntry['month_year'] = $input['month_year'];
    //                     $monthlyEntry['batch_id'] = $batchId;
    //                     $this->casualEmployeeTimesheetRepository->createMonthlyTimesheet($monthlyEntry);
    //                 }
    //             }
    //         });

    //         return redirect()
    //             ->route('casual-employee-timesheets.index')
    //             ->with('success', 'Timesheet batch updated successfully.');
    //     } catch (\Exception $e) {
    //         return redirect()
    //             ->back()
    //             ->withInput()
    //             ->withErrors(['error' => 'Error updating timesheet batch: ' . $e->getMessage()]);
    //     }
    // }

    public function update(CasualEmployeeTimesheetRequest $request, $batchId)
    {
        $input = $request->all();

        try {
            \DB::transaction(function () use ($input, $batchId) {
                // Delete existing entries for this batch
                HourlyTimesheet::where('batch_id', $batchId)->delete();
                MonthlyTimesheet::where('batch_id', $batchId)->delete();

                // Create new hourly timesheets
                if (!empty($input['hourly_entries'])) {
                    foreach ($input['hourly_entries'] as $hourlyEntry) {
                        $hourlyEntry['timesheet_date'] = $input['timesheet_date'];
                        $hourlyEntry['batch_id'] = $batchId;
                        $hourlyEntry['client_name'] = $input['client_name'] ?? null;
                        $hourlyEntry['project_name'] = $input['project_name'] ?? null;

                        $this->casualEmployeeTimesheetRepository->createHourlyTimesheet($hourlyEntry);
                    }
                }

                // Create new monthly timesheets
                if (!empty($input['monthly_entries'])) {
                    foreach ($input['monthly_entries'] as $monthlyEntry) {
                        $monthlyEntry['month_year'] = $input['month_year'];
                        $monthlyEntry['batch_id'] = $batchId;
                        $monthlyEntry['client_name'] = $input['client_name'] ?? null;
                        $monthlyEntry['project_name'] = $input['project_name'] ?? null;

                        $this->casualEmployeeTimesheetRepository->createMonthlyTimesheet($monthlyEntry);
                    }
                }
            });

            return redirect()
                ->route('casual-employee-timesheets.index')
                ->with('success', 'Timesheet batch updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Error updating timesheet batch: ' . $e->getMessage()]);
        }
    }

    public function destroy($batchId)
    {
        try {
            \DB::transaction(function () use ($batchId) {
                // Delete all timesheets in this batch
                HourlyTimesheet::where('batch_id', $batchId)->delete();
                MonthlyTimesheet::where('batch_id', $batchId)->delete();
            });

            return response()->json(['success' => true, 'message' => 'Timesheet batch deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting timesheet batch: ' . $e->getMessage()], 500);
        }
    }

    public function getEmployeeDetails($id)
    {
        $employee = CasualEmployee::findOrFail($id);

        return response()->json([
            'name' => $employee->name,
            'designations' => $employee->designations
        ]);
    }
}
