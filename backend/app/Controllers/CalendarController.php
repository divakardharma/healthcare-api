<?php

require_once __DIR__ . '/../Services/CalendarService.php';
require_once __DIR__ . '/../Helpers/Response.php';

class CalendarController
{
    private CalendarService $calendarService;

    public function __construct(
        CalendarService $calendarService
    ) {
        $this->calendarService = $calendarService;
    }


    // GET /calendar/day?date=2026-09-10
    public function dayView(
        int $tenantId,
        ?int $providerId = null
    ): void {

        $date = $_GET['date'] ?? date('Y-m-d');

        try {

            $appointments =
                $this->calendarService->getDayView(
                    $tenantId,
                    $date,
                    $providerId
                );

            Response::success(
                $appointments,
                'Day view fetched successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    // GET /calendar/range?start_date=2026-09-01&end_date=2026-09-30
    public function rangeView(
        int $tenantId,
        ?int $providerId = null
    ): void {

        $startDate = $_GET['start_date'] ?? '';
        $endDate   = $_GET['end_date'] ?? '';

        try {

            $appointments =
                $this->calendarService->getRangeView(
                    $tenantId,
                    $startDate,
                    $endDate,
                    $providerId
                );

            Response::success(
                $appointments,
                'Range view fetched successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    // GET /calendar/upcoming
    public function upcoming(
        int $tenantId,
        ?int $providerId = null
    ): void {

        try {

            $appointments =
                $this->calendarService->getUpcoming(
                    $tenantId,
                    $providerId
                );

            Response::success(
                $appointments,
                'Upcoming appointments fetched successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    // GET /calendar/appointments/{id}/tooltip
    public function tooltip(
        int $tenantId,
        int $appointmentId
    ): void {

        try {

            $tooltip =
                $this->calendarService->getTooltip(
                    $tenantId,
                    $appointmentId
                );

            Response::success(
                $tooltip,
                'Tooltip details fetched successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                404
            );
        }
    }
}