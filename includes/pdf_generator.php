<?php
/**
 * HTML Ticket Generator
 * Generates beautiful HTML tickets in Ethiopian Airlines Style
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/fpdf.php';

class TicketPDF extends FPDF
{
    function Header()
    {
        // No automatic header
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'EthioServe Inc. | Electronic Ticket Receipt | Generated: ' . date('Y-m-d H:i'), 0, 0, 'C');
    }

    function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', ($x + $w) * $k, ($hp - $y) * $k, ($x + $w) * $k, ($hp - ($y + $MyArc)) * $k, ($x + $w) * $k, ($hp - $yc) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', ($x + $w) * $k, ($hp - ($y + $h - $MyArc)) * $k, ($x + $w - $MyArc) * $k, ($hp - ($y + $h)) * $k, $xc * $k, ($hp - ($y + $h)) * $k));
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', ($x + $MyArc) * $k, ($hp - ($y + $h)) * $k, $x * $k, ($hp - ($y + $h - $MyArc)) * $k, $x * $k, ($hp - $yc) * $k));
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x * $k, ($hp - ($y + $MyArc)) * $k, ($x + $MyArc) * $k, ($hp - $y) * $k, $xc * $k, ($hp - $y) * $k));
        $this->_out($op);
    }
}

function generateTicketFile($booking)
{
    return generatePDFTicketFile($booking);
}

function generateHTMLTicketFile($booking)
{
    return generatePDFTicketFile($booking);
}

function generatePDFTicketFile($booking)
{
    $pdf = new TicketPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 10);

    // Dates
    $dateObj = new DateTime($booking['travel_date']);
    $travelDate = strtoupper($dateObj->format('d M Y'));
    $dayName = strtoupper($dateObj->format('l'));

    // --- TOP HEADER ---
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, "$travelDate   >   $travelDate TRIP TO " . strtoupper($booking['destination']), 'B', 1, 'L');
    $pdf->Ln(5);

    // --- PREPARED FOR & LOGO ---
    $yStart = $pdf->GetY();

    // Left side: Passenger info
    $passengerNames = explode('|', $booking['passenger_names']);
    $primaryPassenger = strtoupper($passengerNames[0]);

    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(100, 5, 'PREPARED FOR', 0, 1);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(100, 8, $primaryPassenger, 0, 1);
    $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(30, 5, 'RESERVATION CODE', 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 5, $booking['booking_reference'], 0, 1);

    // Right side: Logo (Text based for now)
    $pdf->SetXY(120, $yStart);
    $pdf->SetFont('Times', 'B', 24);
    $pdf->SetTextColor(139, 0, 0); // Dark Red
    $pdf->Cell(0, 10, 'EthioServe', 0, 1, 'R');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(218, 165, 32); // Gold
    $pdf->Cell(0, 5, 'A STAR TRANSPORT MEMBER', 0, 1, 'R');
    $pdf->SetTextColor(0, 0, 0); // Reset color

    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 8, 'Your ticket is confirmed. Please present this at boarding.', 'B', 1);
    $pdf->Ln(5);

    // --- DEPARTURE BAR ---
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Rect($pdf->GetX(), $pdf->GetY(), 190, 15, 'F');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(10, 15, 'BUS', 0, 0, 'C'); // Icon placeholder
    $pdf->Cell(100, 15, "DEPARTURE: $dayName " . strtoupper($dateObj->format('d M')), 0, 0);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(80, 15, 'Please verify times prior to departure', 0, 1, 'R');
    $pdf->SetTextColor(0, 0, 0);

    // --- MAIN GRID ---
    $pdf->Ln(2);
    $yGrid = $pdf->GetY();
    $col1W = 50;
    $col2W = 90;
    $col3W = 50;
    $height = 60;

    // Column 1 (Operator)
    $pdf->SetFillColor(224, 224, 224); // Light Grey
    $pdf->Rect($pdf->GetX(), $yGrid, $col1W, $height, 'F');
    $pdf->SetXY($pdf->GetX() + 2, $yGrid + 2);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 77, 64);
    $pdf->Cell($col1W - 4, 5, strtoupper($booking['company_name']), 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell($col1W - 4, 8, 'BUS-' . substr($booking['booking_reference'], -4), 0, 1);
    $pdf->Ln(5);

    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell($col1W - 4, 4, 'Duration:', 0, 1);
    $deptTimeObj = new DateTime($booking['departure_time']);
    $arrTimeObj = new DateTime($booking['arrival_time']);
    $interval = $deptTimeObj->diff($arrTimeObj);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($col1W - 4, 5, $interval->format('%hh %im'), 0, 1);

    $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell($col1W - 4, 4, 'Cabin:', 0, 1);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($col1W - 4, 5, 'Standard', 0, 1);

    $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell($col1W - 4, 4, 'Status:', 0, 1);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($col1W - 4, 5, 'Confirmed', 0, 1);

    // Column 2 (Route)
    $pdf->SetXY(10 + $col1W, $yGrid);
    $pdf->SetLeftMargin(10 + $col1W);
    // Draw vertical line
    $pdf->Line(10 + $col1W, $yGrid, 10 + $col1W, $yGrid + $height);

    $pdf->Ln(2);
    // Origin -> Dest
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(20, 6, strtoupper(substr($booking['origin'], 0, 3)), 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(5, 6, '>', 0, 0, 'C');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(20, 6, strtoupper(substr($booking['destination'], 0, 3)), 0, 1);

    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(25, 4, strtoupper($booking['origin']), 0, 0);
    $pdf->Cell(25, 4, strtoupper($booking['destination']), 0, 1, 'R'); // Right align dest name?

    $pdf->Ln(5);

    // Times
    $deptTime = $deptTimeObj->format('g:i a');
    $arrTime = $arrTimeObj->format('g:i a');

    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(40, 4, 'Departing At:', 0, 0);
    $pdf->Cell(40, 4, 'Arriving At:', 0, 1, 'R');

    $pdf->SetFont('Arial', '', 14);
    $pdf->Cell(40, 8, $deptTime, 0, 0);
    $pdf->Cell(40, 8, $arrTime, 0, 1, 'R');

    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(40, 4, 'STOP: ' . strtoupper($booking['pickup_point']), 0, 0);
    $pdf->Cell(40, 4, 'STOP: ' . strtoupper($booking['dropoff_point']), 0, 1, 'R');

    // Column 3 (Bus Info)
    $pdf->SetLeftMargin(10); // Reset margin
    $pdf->SetXY(10 + $col1W + $col2W, $yGrid);
    $pdf->Line(10 + $col1W + $col2W, $yGrid, 10 + $col1W + $col2W, $yGrid + $height);

    $pdf->SetXY(10 + $col1W + $col2W + 2, $yGrid + 2);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell($col3W, 4, 'Bus Type:', 0, 1);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($col3W, 5, strtoupper($booking['bus_type']), 0, 1);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell($col3W, 6, '#' . $booking['bus_number'], 0, 1);

    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell($col3W, 4, 'Seats:', 0, 1);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($col3W, 5, $booking['seat_numbers'], 0, 1);

    // Draw bottom border of grid
    $pdf->Line(10, $yGrid + $height, 10 + 190, $yGrid + $height);

    $pdf->SetY($yGrid + $height + 5);

    // --- PASSENGER TABLE ---
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(80, 8, 'Passenger Name', 1, 0, 'L', true);
    $pdf->Cell(50, 8, 'Seats', 1, 0, 'L', true);
    $pdf->Cell(60, 8, 'Ticket Status', 1, 1, 'L', true);

    $pdf->SetFont('Arial', '', 9);
    $seats = explode(',', $booking['seat_numbers']);
    foreach ($passengerNames as $i => $name) {
        $seat = isset($seats[$i]) ? $seats[$i] : (isset($seats[0]) ? $seats[0] : 'Any');
        $pdf->Cell(80, 8, '> ' . strtoupper($name), 1, 0, 'L');
        $pdf->Cell(50, 8, $seat, 1, 0, 'L');
        $pdf->Cell(60, 8, 'Check-In Required', 1, 1, 'L');
    }

    // Save to file
    $tempDir = __DIR__ . '/../temp';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    $filename = 'Ticket_' . $booking['booking_reference'] . '.pdf';
    $filepath = $tempDir . '/' . $filename;

    $pdf->Output('F', $filepath);

    return [
        'filename' => $filename,
        'filepath' => $filepath
    ];
}
?>