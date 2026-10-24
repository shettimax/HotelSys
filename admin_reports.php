<?php
// admin_reports.php - Universal Report Generator
// FIXED: Auto-width columns, user info in footer, Excel XML formatting

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

// Load hotel config for branding
$config = include __DIR__ . '/config.php';
$hotel = isset($config['hotel']) ? $config['hotel'] : array();
$hotel_name = isset($hotel['name']) ? $hotel['name'] : 'Hotel Management System';
$hotel_tagline = isset($hotel['tagline']) ? $hotel['tagline'] : '';
$hotel_address = '';
if (isset($hotel['address_line1']) && !empty($hotel['address_line1'])) {
    $hotel_address = $hotel['address_line1'];
    if (isset($hotel['address_line2']) && !empty($hotel['address_line2'])) {
        $hotel_address .= ' ' . $hotel['address_line2'];
    }
}
$hotel_phone = isset($hotel['phone']) ? $hotel['phone'] : '';
$hotel_email = isset($hotel['email']) ? $hotel['email'] : '';

// Default filters
$report_type = isset($_GET['report']) ? $_GET['report'] : 'bookings';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

// Staff attendance filters
$staff_filter = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;
$attendance_status = isset($_GET['attendance_status']) ? $_GET['attendance_status'] : 'all';

// Reset to default dates
if (isset($_GET['reset'])) {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-d');
    $report_type = isset($_GET['report']) ? $_GET['report'] : 'bookings';
    header('Location: admin_reports.php?report=' . $report_type . '&start_date=' . $start_date . '&end_date=' . $end_date);
    exit;
}

// ============================================================
// EXCEL EXPORT - Using Excel XML with proper formatting
// ============================================================
if ($format === 'xlsx' || $format === 'excel') {
    generate_excel_report($report_type, $start_date, $end_date, $staff_filter, $attendance_status);
    exit;
}

// ============================================================
// CSV EXPORT
// ============================================================
if ($format === 'csv') {
    generate_csv_report($report_type, $start_date, $end_date, $staff_filter, $attendance_status);
    exit;
}

// ============================================================
// CSV GENERATION FUNCTION
// ============================================================
function generate_csv_report($type, $start, $end, $staff_filter = 0, $attendance_status = 'all') {
    global $conn, $hotel_name, $hotel_tagline, $hotel_address, $hotel_phone, $hotel_email;
    
    $admin_user = get_current_admin_fullname();
    if (empty($admin_user)) {
        $admin_user = 'System';
    }
    
    $sql = get_report_sql($type, $start, $end, $staff_filter, $attendance_status);
    $report_label = get_report_label($type);
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die('Query error: ' . mysqli_error($conn));
    }

    $clean_hotel_name = preg_replace('/[^a-zA-Z0-9]/', '_', $hotel_name);
    $filename = $clean_hotel_name . "_" . str_replace(' ', '_', $report_label) . "_" . $start . "_to_" . $end . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    
    // Hotel Header
    fputcsv($output, array(''));
    fputcsv($output, array($hotel_name));
    if (!empty($hotel_tagline)) {
        fputcsv($output, array($hotel_tagline));
    }
    if (!empty($hotel_address)) {
        fputcsv($output, array($hotel_address));
    }
    $contact_parts = array();
    if (!empty($hotel_phone)) $contact_parts[] = 'Tel: ' . $hotel_phone;
    if (!empty($hotel_email)) $contact_parts[] = 'Email: ' . $hotel_email;
    if (!empty($contact_parts)) {
        fputcsv($output, array(implode(' | ', $contact_parts)));
    }
    fputcsv($output, array(str_repeat('-', 50)));
    fputcsv($output, array('REPORT: ' . strtoupper($report_label)));
    fputcsv($output, array('Period: ' . date('M d, Y', strtotime($start)) . ' - ' . date('M d, Y', strtotime($end))));
    fputcsv($output, array('Generated: ' . date('Y-m-d H:i:s') . ' by ' . $admin_user));
    fputcsv($output, array(''));
    
    // Data
    $first_row = mysqli_fetch_assoc($result);
    if ($first_row) {
        fputcsv($output, array_keys($first_row));
        fputcsv($output, $first_row);
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, $row);
        }
    } else {
        fputcsv($output, array('No data found for selected filters'));
    }
    
    fputcsv($output, array(''));
    fputcsv($output, array('--- End of Report ---'));
    fputcsv($output, array('Generated by ' . $admin_user . ' on ' . date('Y-m-d H:i:s')));
    fputcsv($output, array('Generated by ' . $hotel_name . ' Management System'));
    
    fclose($output);
    exit;
}

// ============================================================
// EXCEL GENERATION - Using Excel XML with proper formatting
// ============================================================
function generate_excel_report($type, $start, $end, $staff_filter = 0, $attendance_status = 'all') {
    global $conn, $hotel_name, $hotel_tagline, $hotel_address, $hotel_phone, $hotel_email;
    
    // Get current admin user
    $admin_user = get_current_admin_fullname();
    if (empty($admin_user)) {
        $admin_user = 'System';
    }
    
    $sql = get_report_sql($type, $start, $end, $staff_filter, $attendance_status);
    $report_label = get_report_label($type);
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die('Query error: ' . mysqli_error($conn));
    }
    
    $rows = array();
    $headers = array();
    $first_row = mysqli_fetch_assoc($result);
    if ($first_row) {
        $headers = array_keys($first_row);
        $rows[] = $first_row;
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }

    $clean_hotel_name = preg_replace('/[^a-zA-Z0-9]/', '_', $hotel_name);
    $filename = $clean_hotel_name . "_" . str_replace(' ', '_', $report_label) . "_" . $start . "_to_" . $end . ".xls";

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Build column widths based on header text length
    $col_widths = array();
    foreach ($headers as $idx => $header) {
        $max_len = strlen($header);
        foreach ($rows as $row) {
            $val = isset($row[$header]) ? (string)$row[$header] : '';
            $len = strlen($val);
            if ($len > $max_len) $max_len = $len;
        }
        // Set minimum width, max 50 characters
        $col_widths[] = min(max($max_len + 4, 12), 50);
    }
    
    // Build the Excel XML
    $excel = '<?xml version="1.0" encoding="UTF-8"?>
    <?mso-application progid="Excel.Sheet"?>
    <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
              xmlns:o="urn:schemas-microsoft-com:office:office"
              xmlns:x="urn:schemas-microsoft-com:office:excel"
              xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    <Styles>
        <Style ss:ID="Default" ss:Name="Normal">
            <Alignment ss:Vertical="Bottom"/>
            <Borders/>
            <Font ss:FontName="Arial" ss:Size="10"/>
            <Interior/>
            <NumberFormat/>
            <Protection/>
        </Style>
        
        <!-- Hotel Name Style -->
        <Style ss:ID="HotelName">
            <Font ss:FontName="Arial" ss:Size="18" ss:Bold="1"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Interior ss:Color="#1a1a1a" ss:Pattern="Solid"/>
            <Font ss:Color="#FFFFFF"/>
        </Style>
        
        <!-- Tagline Style -->
        <Style ss:ID="Tagline">
            <Font ss:FontName="Arial" ss:Size="11" ss:Italic="1"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Font ss:Color="#555555"/>
        </Style>
        
        <!-- Address Style -->
        <Style ss:ID="Address">
            <Font ss:FontName="Arial" ss:Size="10"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Font ss:Color="#666666"/>
        </Style>
        
        <!-- Contact Style -->
        <Style ss:ID="Contact">
            <Font ss:FontName="Arial" ss:Size="10"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Font ss:Color="#666666"/>
        </Style>
        
        <!-- Separator Style -->
        <Style ss:ID="Separator">
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
            </Borders>
        </Style>
        
        <!-- Report Title Style -->
        <Style ss:ID="ReportTitle">
            <Font ss:FontName="Arial" ss:Size="14" ss:Bold="1"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Interior ss:Color="#E8E8E8" ss:Pattern="Solid"/>
        </Style>
        
        <!-- Report Meta Style -->
        <Style ss:ID="ReportMeta">
            <Font ss:FontName="Arial" ss:Size="10"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Font ss:Color="#888888"/>
        </Style>
        
        <!-- Header Row Style -->
        <Style ss:ID="HeaderRow">
            <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#1a1a1a" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        
        <!-- Data Row Style -->
        <Style ss:ID="DataRow">
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        
        <!-- Data Row Alt Style -->
        <Style ss:ID="DataRowAlt">
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        
        <!-- Attendance Rate High (Green) -->
        <Style ss:ID="RateHigh">
            <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#28A745"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        
        <!-- Attendance Rate Mid (Yellow) -->
        <Style ss:ID="RateMid">
            <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#FFC107"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        
        <!-- Attendance Rate Low (Red) -->
        <Style ss:ID="RateLow">
            <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#DC3545"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        
        <!-- Status Completed -->
        <Style ss:ID="StatusCompleted">
            <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#28A745"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        </Style>
        
        <!-- Status In Progress -->
        <Style ss:ID="StatusInProgress">
            <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#FFC107"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        </Style>
        
        <!-- Status Absent -->
        <Style ss:ID="StatusAbsent">
            <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#DC3545"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        </Style>
        
        <!-- Punctuality Late -->
        <Style ss:ID="PunctualityLate">
            <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#DC3545"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        </Style>
        
        <!-- Punctuality On Time -->
        <Style ss:ID="PunctualityOnTime">
            <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#28A745"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        </Style>
        
        <!-- Footer Style -->
        <Style ss:ID="Footer">
            <Font ss:FontName="Arial" ss:Size="9" ss:Italic="1" ss:Color="#999999"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        </Style>
        
        <!-- Footer User Style -->
        <Style ss:ID="FooterUser">
            <Font ss:FontName="Arial" ss:Size="9" ss:Italic="1" ss:Color="#666666"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        </Style>
    </Styles>
    <Worksheet ss:Name="' . h($report_label) . '">
        <Table>';
    
    // Count columns for merging
    $col_count = count($headers);
    if ($col_count == 0) $col_count = 1;
    $col_span = $col_count;
    
    // Set column widths
    foreach ($col_widths as $idx => $width) {
        $excel .= '
            <Column ss:Width="' . ($width * 7) . '"/>';
    }
    
    // Hotel Header
    $excel .= '
            <Row>
                <Cell ss:StyleID="HotelName" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . h($hotel_name) . '</Data></Cell>
            </Row>';
    
    if (!empty($hotel_tagline)) {
        $excel .= '
            <Row>
                <Cell ss:StyleID="Tagline" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . h($hotel_tagline) . '</Data></Cell>
            </Row>';
    }
    
    if (!empty($hotel_address)) {
        $excel .= '
            <Row>
                <Cell ss:StyleID="Address" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . h($hotel_address) . '</Data></Cell>
            </Row>';
    }
    
    $contact_parts = array();
    if (!empty($hotel_phone)) $contact_parts[] = 'Tel: ' . h($hotel_phone);
    if (!empty($hotel_email)) $contact_parts[] = 'Email: ' . h($hotel_email);
    if (!empty($contact_parts)) {
        $excel .= '
            <Row>
                <Cell ss:StyleID="Contact" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . implode(' | ', $contact_parts) . '</Data></Cell>
            </Row>';
    }
    
    // Separator
    $excel .= '
            <Row>
                <Cell ss:StyleID="Separator" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String"></Data></Cell>
            </Row>';
    
    // Report Title
    $excel .= '
            <Row>
                <Cell ss:StyleID="ReportTitle" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . strtoupper(h($report_label)) . '</Data></Cell>
            </Row>';
    
    // Report Meta
    $excel .= '
            <Row>
                <Cell ss:StyleID="ReportMeta" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">Period: ' . date('M d, Y', strtotime($start)) . ' - ' . date('M d, Y', strtotime($end)) . '</Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="ReportMeta" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">Generated: ' . date('Y-m-d H:i:s') . ' by ' . h($admin_user) . '</Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="Separator" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String"></Data></Cell>
            </Row>
            <Row>
                <Cell ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String"></Data></Cell>
            </Row>';
    
    // Data Table Headers
    if (!empty($headers) && !empty($rows)) {
        $excel .= '
            <Row>';
        foreach ($headers as $header) {
            $excel .= '
                <Cell ss:StyleID="HeaderRow"><Data ss:Type="String">' . h($header) . '</Data></Cell>';
        }
        $excel .= '
            </Row>';
        
        // Data Rows
        $row_num = 0;
        foreach ($rows as $row) {
            $row_style = ($row_num % 2 == 0) ? 'DataRow' : 'DataRowAlt';
            $excel .= '
            <Row>';
            foreach ($headers as $header) {
                $val = isset($row[$header]) ? $row[$header] : '';
                $cell_style = $row_style;
                
                // Apply special formatting
                if (strpos($header, 'Attendance Rate') !== false) {
                    $num_val = floatval($val);
                    if ($num_val >= 90) $cell_style = 'RateHigh';
                    elseif ($num_val >= 75) $cell_style = 'RateMid';
                    else $cell_style = 'RateLow';
                } elseif (strpos($header, 'Punctuality') !== false) {
                    if ($val == 'Late') $cell_style = 'PunctualityLate';
                    elseif ($val == 'On Time') $cell_style = 'PunctualityOnTime';
                } elseif (strpos($header, 'Status') !== false) {
                    if ($val == 'Completed' || $val == 'completed') $cell_style = 'StatusCompleted';
                    elseif ($val == 'In Progress' || $val == 'in progress') $cell_style = 'StatusInProgress';
                    elseif ($val == 'Absent' || $val == 'absent') $cell_style = 'StatusAbsent';
                }
                
                // Truncate long values for display
                if (strlen($val) > 50) {
                    $val = substr($val, 0, 47) . '...';
                }
                
                $excel .= '
                <Cell ss:StyleID="' . $cell_style . '"><Data ss:Type="String">' . h($val) . '</Data></Cell>';
            }
            $excel .= '
            </Row>';
            $row_num++;
        }
    } else {
        $excel .= '
            <Row>
                <Cell ss:MergeAcross="' . ($col_span - 1) . '" ss:StyleID="DataRow"><Data ss:Type="String">No data found for selected filters</Data></Cell>
            </Row>';
    }
    
    // Footer
    $excel .= '
            <Row>
                <Cell ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String"></Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="Separator" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String"></Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="Footer" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">--- End of Report ---</Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="FooterUser" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">Generated by ' . h($admin_user) . ' on ' . date('Y-m-d H:i:s') . '</Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="Footer" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . h($hotel_name) . ' Management System</Data></Cell>
            </Row>';
    
    $excel .= '
        </Table>
        <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
            <PageSetup>
                <PageMargins x:Bottom="0.75" x:Left="0.7" x:Right="0.7" x:Top="0.75"/>
            </PageSetup>
            <Print>
                <ValidPrinterInfo/>
                <PaperSizeIndex>9</PaperSizeIndex>
                <HorizontalResolution>600</HorizontalResolution>
                <VerticalResolution>600</VerticalResolution>
            </Print>
            <Selected/>
            <Panes>
                <Pane>
                    <Number>3</Number>
                    <ActiveRow>6</ActiveRow>
                    <ActiveCol>0</ActiveCol>
                </Pane>
            </Panes>
            <ProtectObjects>False</ProtectObjects>
            <ProtectScenarios>False</ProtectScenarios>
        </WorksheetOptions>
    </Worksheet>
</Workbook>';
    
    echo $excel;
    exit;
}

// ============================================================
// HELPERS
// ============================================================
function get_report_label($type) {
    $labels = array(
        'bookings' => 'Bookings Report',
        'attendance' => 'Staff Attendance Log',
        'attendance_summary' => 'Staff Attendance Summary',
        'revenue' => 'Daily Revenue Report',
        'occupancy' => 'Room Occupancy Report',
        'checkins' => 'Daily Check-ins Report',
        'extensions' => 'Stay Extensions Report',
        'top_guests' => 'Top Guests by Spend',
        'rooms' => 'Rooms Report',
        'buildings' => 'Buildings Report',
        'disputes' => 'Disputes & Complaints Report',
        'refunds' => 'Refunds Report',
        'logs' => 'Admin Activity Logs',
        'staff_sales' => 'Staff Sales Performance'
    );
    return isset($labels[$type]) ? $labels[$type] : 'Report';
}

function get_report_sql($type, $start, $end, $staff_filter = 0, $attendance_status = 'all') {
    global $conn;
    $staff_where = $staff_filter > 0 ? "AND s.id = $staff_filter" : "";
    $status_filter = "";
    
    switch ($type) {
        case 'bookings':
            return "
                SELECT 
                    b.id as 'Booking ID',
                    i.invoice_number as 'Invoice',
                    CONCAT(c.firstname, ' ', c.lastname) as 'Guest',
                    c.phone as 'Phone',
                    r.room_number as 'Room',
                    bu.name as 'Building',
                    DATE(b.checkin_date) as 'Check-in',
                    DATE(b.checkout_date) as 'Check-out',
                    b.nights as 'Nights',
                    b.amount as 'Amount (₦)',
                    b.status as 'Status'
                FROM bookings b
                JOIN customers c ON b.customer_id = c.id
                JOIN rooms r ON b.room_id = r.id
                JOIN buildings bu ON r.building_id = bu.id
                LEFT JOIN invoices i ON b.id = i.booking_id
                WHERE b.checkin_date BETWEEN '$start' AND '$end'
                ORDER BY b.checkin_date DESC
            ";

        case 'attendance':
            return "
                SELECT 
                    s.pnumber as 'Staff ID',
                    s.name as 'Staff Name',
                    s.department as 'Department',
                    DATE(sa.date) as 'Date',
                    TIME(sa.clock_in) as 'Clock In',
                    TIME(sa.clock_out) as 'Clock Out',
                    TIMEDIFF(sa.clock_out, sa.clock_in) as 'Duration',
                    CASE 
                        WHEN TIME(sa.clock_in) > '09:15:00' THEN 'Late'
                        WHEN TIME(sa.clock_in) <= '09:00:00' THEN 'On Time'
                        ELSE 'On Time'
                    END as 'Punctuality',
                    CASE 
                        WHEN sa.clock_out IS NOT NULL THEN 'Completed'
                        WHEN sa.clock_in IS NOT NULL THEN 'In Progress'
                        ELSE 'Absent'
                    END as 'Status',
                    u.fullname as 'Recorded By'
                FROM staff_attendance sa
                JOIN staff s ON sa.staff_id = s.id
                LEFT JOIN users_admin u ON sa.recorded_by = u.id
                WHERE sa.date BETWEEN '$start' AND '$end'
                $staff_where
                ORDER BY sa.date DESC, s.name ASC
            ";

        case 'attendance_summary':
            if ($attendance_status == 'present') {
                $status_filter = "AND sa.clock_in IS NOT NULL";
            } elseif ($attendance_status == 'late') {
                $status_filter = "AND TIME(sa.clock_in) > '09:15:00'";
            } elseif ($attendance_status == 'on_time') {
                $status_filter = "AND TIME(sa.clock_in) <= '09:00:00'";
            }
            return "
                SELECT 
                    s.pnumber as 'Staff ID',
                    s.name as 'Staff Name',
                    s.department as 'Department',
                    COUNT(sa.id) as 'Days Present',
                    (SELECT COUNT(DISTINCT date) FROM staff_attendance sa2 WHERE sa2.staff_id = s.id AND sa2.date BETWEEN '$start' AND '$end') as 'Total Working Days',
                    ROUND((COUNT(sa.id) / NULLIF((SELECT COUNT(DISTINCT date) FROM staff_attendance sa2 WHERE sa2.staff_id = s.id AND sa2.date BETWEEN '$start' AND '$end'), 0)) * 100, 2) as 'Attendance Rate %',
                    SUM(CASE WHEN TIME(sa.clock_in) > '09:15:00' THEN 1 ELSE 0 END) as 'Late Arrivals',
                    SUM(CASE WHEN TIME(sa.clock_in) <= '09:00:00' THEN 1 ELSE 0 END) as 'On Time',
                    SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(sa.clock_out, sa.clock_in)))) as 'Total Hours Worked',
                    ROUND(AVG(TIME_TO_SEC(TIMEDIFF(sa.clock_out, sa.clock_in))) / 3600, 2) as 'Avg Hours/Day'
                FROM staff_attendance sa
                JOIN staff s ON sa.staff_id = s.id
                WHERE sa.date BETWEEN '$start' AND '$end'
                $staff_where
                $status_filter
                GROUP BY s.id
                ORDER BY `Attendance Rate %` DESC
            ";

        case 'revenue':
            return "
                SELECT 
                    DATE(b.checkin_date) as 'Date',
                    COUNT(*) as 'Bookings',
                    SUM(b.amount) as 'Total Revenue (₦)',
                    AVG(b.amount) as 'Avg per Booking (₦)'
                FROM bookings b
                WHERE b.status = 'paid'
                  AND b.checkin_date BETWEEN '$start' AND '$end'
                GROUP BY DATE(b.checkin_date)
                ORDER BY Date DESC
            ";

        case 'occupancy':
            return "
                SELECT 
                    r.room_number as 'Room',
                    bu.name as 'Building',
                    COUNT(b.id) as 'Bookings',
                    SUM(b.nights) as 'Total Nights Occupied',
                    ROUND((SUM(b.nights) / (DATEDIFF('$end', '$start') + 1)) * 100, 2) as 'Occupancy %'
                FROM rooms r
                LEFT JOIN buildings bu ON r.building_id = bu.id
                LEFT JOIN bookings b ON r.id = b.room_id 
                    AND b.status = 'paid'
                    AND b.checkout_status = 'checked_out'
                    AND b.checkout_date <= '$end'
                    AND b.checkin_date >= '$start'
                GROUP BY r.id
                ORDER BY `Occupancy %` DESC
            ";

        case 'checkins':
            return "
                SELECT 
                    DATE(b.actual_checkin_datetime) as 'Check-in Date',
                    COUNT(*) as 'Guests Checked In',
                    GROUP_CONCAT(CONCAT(c.firstname, ' ', c.lastname, ' (Room ', r.room_number, ')') SEPARATOR '; ') as 'Guests'
                FROM bookings b
                JOIN customers c ON b.customer_id = c.id
                JOIN rooms r ON b.room_id = r.id
                WHERE b.actual_checkin_datetime IS NOT NULL
                  AND DATE(b.actual_checkin_datetime) BETWEEN '$start' AND '$end'
                GROUP BY DATE(b.actual_checkin_datetime)
                ORDER BY DATE(b.actual_checkin_datetime) DESC
            ";

        case 'extensions':
            return "
                SELECT 
                    se.id as 'Extension ID',
                    i.invoice_number as 'Invoice',
                    CONCAT(c.firstname, ' ', c.lastname) as 'Guest',
                    r.room_number as 'Room',
                    se.original_checkout as 'Original Checkout',
                    se.new_checkout as 'New Checkout',
                    se.additional_nights as 'Extra Nights',
                    se.additional_amount as 'Extra Revenue (₦)',
                    DATE(se.extended_at) as 'Extended On',
                    u.fullname as 'Extended By'
                FROM stay_extensions se
                JOIN bookings b ON se.booking_id = b.id
                JOIN invoices i ON b.id = i.booking_id
                JOIN customers c ON b.customer_id = c.id
                JOIN rooms r ON b.room_id = r.id
                LEFT JOIN users_admin u ON se.extended_by = u.id
                WHERE DATE(se.extended_at) BETWEEN '$start' AND '$end'
                ORDER BY se.extended_at DESC
            ";

        case 'top_guests':
            return "
                SELECT 
                    CONCAT(c.firstname, ' ', c.lastname) as 'Guest Name',
                    c.phone as 'Phone',
                    c.email as 'Email',
                    COUNT(b.id) as 'Total Bookings',
                    SUM(b.amount) as 'Total Spent (₦)',
                    AVG(b.amount) as 'Average Booking (₦)'
                FROM customers c
                JOIN bookings b ON c.id = b.customer_id
                WHERE b.status = 'paid'
                  AND b.checkin_date BETWEEN '$start' AND '$end'
                GROUP BY c.id
                ORDER BY SUM(b.amount) DESC
                LIMIT 20
            ";

        case 'rooms':
            return "
                SELECT 
                    r.id as 'Room ID',
                    r.room_number as 'Room Number',
                    bu.name as 'Building',
                    r.capacity as 'Capacity',
                    r.price_per_night as 'Price/Night (₦)',
                    r.status as 'Status',
                    CASE WHEN r.use_system_price = 1 THEN 'Building Base' ELSE 'Manual' END as 'Pricing Type',
                    r.manual_price as 'Manual Price (₦)'
                FROM rooms r
                JOIN buildings bu ON r.building_id = bu.id
                ORDER BY bu.name, r.room_number
            ";

        case 'buildings':
            return "
                SELECT 
                    b.id as 'Building ID',
                    b.name as 'Building Name',
                    b.building_type as 'Type',
                    b.base_price as 'Base Price (₦)',
                    b.description as 'Description',
                    COUNT(r.id) as 'Total Rooms',
                    (SELECT COUNT(*) FROM rooms rr WHERE rr.building_id = b.id AND rr.status = 'available') as 'Available Rooms'
                FROM buildings b
                LEFT JOIN rooms r ON b.id = r.building_id
                GROUP BY b.id
                ORDER BY b.name
            ";

        case 'disputes':
            return "
                SELECT 
                    d.id as 'Dispute ID',
                    i.invoice_number as 'Invoice',
                    CONCAT(c.firstname, ' ', c.lastname) as 'Guest',
                    c.phone as 'Phone',
                    d.subject as 'Subject',
                    d.status as 'Status',
                    d.created_at as 'Reported',
                    d.resolved_at as 'Resolved'
                FROM disputes d
                JOIN bookings b ON d.booking_id = b.id
                JOIN invoices i ON b.id = i.booking_id
                JOIN customers c ON b.customer_id = c.id
                WHERE d.created_at BETWEEN '$start 00:00:00' AND '$end 23:59:59'
                ORDER BY d.created_at DESC
            ";

        case 'refunds':
            return "
                SELECT 
                    p.id as 'Payment ID',
                    i.invoice_number as 'Invoice',
                    CONCAT(c.firstname, ' ', c.lastname) as 'Guest',
                    ABS(p.amount) as 'Refund Amount (₦)',
                    p.method as 'Method',
                    p.created_at as 'Date',
                    COALESCE(ch.refund_description, 'N/A') as 'Reason'
                FROM payments p
                JOIN invoices i ON p.invoice_id = i.id
                JOIN bookings b ON i.booking_id = b.id
                JOIN customers c ON b.customer_id = c.id
                LEFT JOIN checkouts ch ON ch.booking_id = b.id
                WHERE p.amount < 0
                  AND p.status = 'completed'
                  AND p.created_at BETWEEN '$start' AND '$end'
                ORDER BY p.created_at DESC
            ";

        case 'logs':
            return "
                SELECT 
                    l.created_at as 'Date/Time',
                    l.admin_name as 'Admin',
                    l.action as 'Action',
                    l.description as 'Details',
                    l.ip_address as 'IP'
                FROM admin_logs l
                WHERE l.created_at BETWEEN '$start 00:00:00' AND '$end 23:59:59'
                ORDER BY l.created_at DESC
            ";

        case 'staff_sales':
            return "
                SELECT 
                    s.pnumber as 'Staff ID',
                    s.name as 'Staff Name',
                    s.department as 'Department',
                    COUNT(b.id) as 'Bookings Made',
                    SUM(CASE WHEN b.status != 'cancelled' THEN b.amount ELSE 0 END) as 'Total Sales (₦)',
                    ROUND(AVG(CASE WHEN b.status != 'cancelled' THEN b.amount ELSE NULL END), 2) as 'Avg Booking Value (₦)',
                    SUM(CASE WHEN b.status = 'paid' THEN 1 ELSE 0 END) as 'Paid Bookings',
                    SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as 'Cancelled Bookings'
                FROM staff s
                JOIN bookings b ON b.staff_id = s.id
                    AND b.checkin_date BETWEEN '$start' AND '$end'
                WHERE 1=1
                $staff_where
                GROUP BY s.id
                ORDER BY `Total Sales (₦)` DESC
            ";

        default:
            return "SELECT 'Please choose a report type' as Message";
    }
}

// ============================================================
// HTML DISPLAY
// ============================================================
include __DIR__ . '/header.php';

$per_page = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$title = '';
$sql = '';
$total_count = 0;
$staff_list = array();

// Get staff list for attendance filter
$staff_result = mysqli_query($conn, "SELECT id, pnumber, name, department FROM staff WHERE status = 'active' ORDER BY name");
while ($row = mysqli_fetch_assoc($staff_result)) {
    $staff_list[] = $row;
}

// Build query for HTML display
switch ($report_type) {
    case 'bookings':
        $title = 'Bookings Report';
        $sql = get_report_sql($report_type, $start_date, $end_date) . " LIMIT $offset, $per_page";
        $count_sql = "SELECT COUNT(*) as total FROM bookings b WHERE b.checkin_date BETWEEN '$start_date' AND '$end_date'";
        break;

    case 'attendance':
        $title = 'Staff Attendance Log';
        $staff_where = $staff_filter > 0 ? "AND s.id = $staff_filter" : "";
        $sql = get_report_sql($report_type, $start_date, $end_date, $staff_filter) . " LIMIT $offset, $per_page";
        $count_sql = "
            SELECT COUNT(*) as total 
            FROM staff_attendance sa
            JOIN staff s ON sa.staff_id = s.id
            WHERE sa.date BETWEEN '$start_date' AND '$end_date'
            $staff_where
        ";
        break;

    case 'attendance_summary':
        $title = 'Staff Attendance Summary';
        $sql = get_report_sql($report_type, $start_date, $end_date, $staff_filter, $attendance_status) . " LIMIT $offset, $per_page";
        $count_sql = "SELECT COUNT(DISTINCT s.id) as total FROM staff s WHERE s.status = 'active'";
        break;

    case 'revenue':
        $title = 'Daily Revenue Report';
        $sql = get_report_sql($report_type, $start_date, $end_date) . " LIMIT $offset, $per_page";
        $count_sql = "
            SELECT COUNT(DISTINCT DATE(b.checkin_date)) as total
            FROM bookings b
            WHERE b.status = 'paid'
              AND b.checkin_date BETWEEN '$start_date' AND '$end_date'
        ";
        break;

    case 'occupancy':
        $title = 'Room Occupancy Report';
        $sql = get_report_sql($report_type, $start_date, $end_date) . " LIMIT $offset, $per_page";
        $count_sql = "SELECT COUNT(*) as total FROM rooms";
        break;

    case 'checkins':
        $title = 'Daily Check-ins Report';
        $sql = get_report_sql($report_type, $start_date, $end_date) . " LIMIT $offset, $per_page";
        $count_sql = "
            SELECT COUNT(DISTINCT DATE(b.actual_checkin_datetime)) as total
            FROM bookings b
            WHERE b.actual_checkin_datetime IS NOT NULL
              AND DATE(b.actual_checkin_datetime) BETWEEN '$start_date' AND '$end_date'
        ";
        break;

    case 'extensions':
        $title = 'Stay Extensions Report';
        $sql = get_report_sql($report_type, $start_date, $end_date) . " LIMIT $offset, $per_page";
        $count_sql = "
            SELECT COUNT(*) as total
            FROM stay_extensions se
            WHERE DATE(se.extended_at) BETWEEN '$start_date' AND '$end_date'
        ";
        break;

    case 'top_guests':
        $title = 'Top Guests by Total Spend';
        $sql = get_report_sql($report_type, $start_date, $end_date) . " LIMIT $offset, $per_page";
        $count_sql = "
            SELECT COUNT(DISTINCT c.id) as total
            FROM customers c
            JOIN bookings b ON c.id = b.customer_id
            WHERE b.status = 'paid'
              AND b.checkin_date BETWEEN '$start_date' AND '$end_date'
        ";
        break;

    case 'rooms':
        $title = 'Rooms Report';
        $sql = get_report_sql($report_type, $start_date, $end_date) . " LIMIT $offset, $per_page";
        $count_sql = "SELECT COUNT(*) as total FROM rooms";
        break;

    case 'buildings':
        $title = 'Buildings Report';
        $sql = get_report_sql($report_type, $start_date, $end_date) . " LIMIT $offset, $per_page";
        $count_sql = "SELECT COUNT(*) as total FROM buildings";
        break;

    case 'disputes':
        $title = 'Disputes & Complaints Report';
        $sql = get_report_sql($report_type, $start_date, $end_date) . " LIMIT $offset, $per_page";
        $count_sql = "
            SELECT COUNT(*) as total
            FROM disputes d
            WHERE d.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
        ";
        break;

    case 'refunds':
        $title = 'Refunds Report';
        $sql = get_report_sql($report_type, $start_date, $end_date) . " LIMIT $offset, $per_page";
        $count_sql = "
            SELECT COUNT(*) as total
            FROM payments p
            WHERE p.amount < 0
              AND p.status = 'completed'
              AND p.created_at BETWEEN '$start_date' AND '$end_date'
        ";
        break;

    case 'logs':
        $title = 'Admin Activity Logs';
        $sql = get_report_sql($report_type, $start_date, $end_date) . " LIMIT $offset, $per_page";
        $count_sql = "
            SELECT COUNT(*) as total
            FROM admin_logs l
            WHERE l.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
        ";
        break;

    case 'staff_sales':
        $title = 'Staff Sales Performance';
        if (!db_column_exists($conn, 'bookings', 'staff_id')) {
            $sql = "SELECT 'Staff sales tracking is not set up yet - run setup.php to add the required columns.' as Message";
            $count_sql = null;
        } else {
            $sql = get_report_sql($report_type, $start_date, $end_date, $staff_filter) . " LIMIT $offset, $per_page";
            $staff_where = $staff_filter > 0 ? "AND s.id = $staff_filter" : "";
            $count_sql = "
                SELECT COUNT(DISTINCT s.id) as total
                FROM staff s
                JOIN bookings b ON b.staff_id = s.id AND b.checkin_date BETWEEN '$start_date' AND '$end_date'
                WHERE 1=1
                $staff_where
            ";
        }
        break;

    default:
        $title = 'Select a Report';
        $sql = "SELECT 'Please choose a report type from the dropdown' as Message";
}

$result = mysqli_query($conn, $sql);

if (isset($count_sql)) {
    $count_result = mysqli_query($conn, $count_sql);
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        $total_count = $count_row['total'];
    }
}

$total_pages = ceil($total_count / $per_page);

// Attendance stats for dashboard
$attendance_stats = array();
if ($report_type == 'attendance' || $report_type == 'attendance_summary') {
    $stats_sql = "
        SELECT 
            COUNT(DISTINCT s.id) as total_staff,
            COUNT(DISTINCT sa.date) as total_days,
            COUNT(sa.id) as total_clockins,
            SUM(CASE WHEN TIME(sa.clock_in) > '09:15:00' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN TIME(sa.clock_in) <= '09:00:00' THEN 1 ELSE 0 END) as on_time_count,
            SUM(CASE WHEN sa.clock_out IS NOT NULL THEN 1 ELSE 0 END) as clocked_out_count
        FROM staff_attendance sa
        JOIN staff s ON sa.staff_id = s.id
        WHERE sa.date BETWEEN '$start_date' AND '$end_date'
    ";
    $stats_result = mysqli_query($conn, $stats_sql);
    $attendance_stats = mysqli_fetch_assoc($stats_result);
}
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-chart-bar"></i> Reports Generator</h4>
                    </div>
                    <div class="card-body">
                        <form method="get" class="mb-4">
                            <input type="hidden" name="page" value="1">
                            <div class="row align-items-end">
                                <div class="col-md-2">
                                    <label>Report Type</label>
                                    <select name="report" class="form-control" onchange="this.form.submit()">
                                        <option value="bookings" <?php echo $report_type=='bookings'?'selected':''; ?>>Bookings</option>
                                        <option value="revenue" <?php echo $report_type=='revenue'?'selected':''; ?>>Daily Revenue</option>
                                        <option value="occupancy" <?php echo $report_type=='occupancy'?'selected':''; ?>>Room Occupancy</option>
                                        <option value="checkins" <?php echo $report_type=='checkins'?'selected':''; ?>>Daily Check-ins</option>
                                        <option value="extensions" <?php echo $report_type=='extensions'?'selected':''; ?>>Stay Extensions</option>
                                        <option value="top_guests" <?php echo $report_type=='top_guests'?'selected':''; ?>>Top Guests by Spend</option>
                                        <option value="rooms" <?php echo $report_type=='rooms'?'selected':''; ?>>Rooms Report</option>
                                        <option value="buildings" <?php echo $report_type=='buildings'?'selected':''; ?>>Buildings Report</option>
                                        <option value="disputes" <?php echo $report_type=='disputes'?'selected':''; ?>>Disputes</option>
                                        <option value="refunds" <?php echo $report_type=='refunds'?'selected':''; ?>>Refunds</option>
                                        <option value="logs" <?php echo $report_type=='logs'?'selected':''; ?>>Activity Logs</option>
                                        <option value="staff_sales" <?php echo $report_type=='staff_sales'?'selected':''; ?>>Staff Sales Performance</option>
                                        <option value="attendance" <?php echo $report_type=='attendance'?'selected':''; ?>>Attendance Log</option>
                                        <option value="attendance_summary" <?php echo $report_type=='attendance_summary'?'selected':''; ?>>Attendance Summary</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo h($start_date); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label>End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo h($end_date); ?>">
                                </div>
                                <?php if ($report_type == 'attendance' || $report_type == 'attendance_summary' || $report_type == 'staff_sales'): ?>
                                <div class="col-md-2">
                                    <label>Staff Member</label>
                                    <select name="staff_id" class="form-control">
                                        <option value="0">All Staff</option>
                                        <?php foreach ($staff_list as $staff): ?>
                                        <option value="<?php echo h($staff['id']); ?>" <?php echo $staff_filter == $staff['id'] ? 'selected' : ''; ?>>
                                            <?php echo h($staff['pnumber'] . ' - ' . $staff['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <?php if ($report_type == 'attendance' || $report_type == 'attendance_summary'): ?>
                                <div class="col-md-1">
                                    <label>Status</label>
                                    <select name="attendance_status" class="form-control">
                                        <option value="all" <?php echo $attendance_status=='all'?'selected':''; ?>>All</option>
                                        <option value="present" <?php echo $attendance_status=='present'?'selected':''; ?>>Present</option>
                                        <option value="late" <?php echo $attendance_status=='late'?'selected':''; ?>>Late</option>
                                        <option value="on_time" <?php echo $attendance_status=='on_time'?'selected':''; ?>>On Time</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-info btn-block">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </div>
                                <div class="col-md-1">
                                    <a href="?report=<?php echo h($report_type); ?>&reset=1" class="btn btn-secondary btn-block">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-success btn-block" onclick="exportReport('csv')">
                                        <i class="fas fa-file-csv"></i> CSV
                                    </button>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-success btn-block" onclick="exportReport('xlsx')" style="background-color:#1d6f42;border-color:#1d6f42;">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Attendance Stats Cards -->
                        <?php if (($report_type == 'attendance' || $report_type == 'attendance_summary') && !empty($attendance_stats)): ?>
                        <div class="row mb-4">
                            <div class="col-md-2">
                                <div class="card text-center border-secondary">
                                    <div class="card-body">
                                        <h4 class="text-light"><?php echo h($attendance_stats['total_staff'] ?: 0); ?></h4>
                                        <p class="mb-0">Active Staff</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card text-center border-info">
                                    <div class="card-body">
                                        <h4 class="text-info"><?php echo h($attendance_stats['total_days'] ?: 0); ?></h4>
                                        <p class="mb-0">Days Recorded</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card text-center border-success">
                                    <div class="card-body">
                                        <h4 class="text-success"><?php echo h($attendance_stats['total_clockins'] ?: 0); ?></h4>
                                        <p class="mb-0">Total Clock-ins</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card text-center border-warning">
                                    <div class="card-body">
                                        <h4 class="text-warning"><?php echo h($attendance_stats['late_count'] ?: 0); ?></h4>
                                        <p class="mb-0">Late Arrivals</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card text-center border-success">
                                    <div class="card-body">
                                        <h4 class="text-success"><?php echo h($attendance_stats['on_time_count'] ?: 0); ?></h4>
                                        <p class="mb-0">On Time</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card text-center border-primary">
                                    <div class="card-body">
                                        <?php 
                                        $clocked_out = isset($attendance_stats['clocked_out_count']) ? $attendance_stats['clocked_out_count'] : 0;
                                        $total_clocks = isset($attendance_stats['total_clockins']) && $attendance_stats['total_clockins'] > 0 ? $attendance_stats['total_clockins'] : 1;
                                        $completion_rate = round(($clocked_out / $total_clocks) * 100, 1);
                                        ?>
                                        <h4 class="text-primary"><?php echo h($completion_rate); ?>%</h4>
                                        <p class="mb-0">Completed Shifts</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($sql && $result && mysqli_num_rows($result) > 0): ?>
                            <h5><?php echo h($title); ?> (<?php echo date('M j, Y', strtotime($start_date)); ?> – <?php echo date('M j, Y', strtotime($end_date)); ?>)</h5>
                            <p class="text-muted mb-3">Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $per_page, $total_count); ?> of <?php echo h($total_count); ?> records</p>

                            <div class="table-responsive">
                                <table class="table table-bordered table-dark">
                                    <thead>
                                        <tr>
                                            <?php 
                                            mysqli_data_seek($result, 0);
                                            $first_row = mysqli_fetch_assoc($result);
                                            foreach ($first_row as $key => $val) {
                                                echo "<th>" . h($key) . "</th>";
                                            }
                                            mysqli_data_seek($result, 0);
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <?php foreach ($row as $key => $val): ?>
                                                <td>
                                                    <?php 
                                                    // Format special columns
                                                    if (strpos($key, 'Punctuality') !== false) {
                                                        if ($val == 'Late') {
                                                            echo '<span class="badge badge-danger">' . h($val) . '</span>';
                                                        } else {
                                                            echo '<span class="badge badge-success">' . h($val) . '</span>';
                                                        }
                                                    } elseif (strpos($key, 'Status') !== false && strpos($key, 'Attendance Rate') === false) {
                                                        if ($val == 'Absent' || $val == 'absent') {
                                                            echo '<span class="badge badge-danger">' . h($val) . '</span>';
                                                        } elseif ($val == 'Completed' || $val == 'completed') {
                                                            echo '<span class="badge badge-success">' . h($val) . '</span>';
                                                        } elseif ($val == 'In Progress' || $val == 'in progress') {
                                                            echo '<span class="badge badge-warning">' . h($val) . '</span>';
                                                        } else {
                                                            echo h($val);
                                                        }
                                                    } elseif (strpos($key, 'Attendance Rate') !== false) {
                                                        $num_val = floatval($val);
                                                        if ($num_val >= 90) {
                                                            echo '<span class="text-success">' . h($val) . '%</span>';
                                                        } elseif ($num_val >= 75) {
                                                            echo '<span class="text-warning">' . h($val) . '%</span>';
                                                        } else {
                                                            echo '<span class="text-danger">' . h($val) . '%</span>';
                                                        }
                                                    } else {
                                                        echo h($val);
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Report pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, array('page' => $page - 1))); ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link"><i class="fas fa-chevron-left"></i> Previous</span>
                                        </li>
                                    <?php endif; ?>

                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, array('page' => 1))); ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, array('page' => $i))); ?>">
                                                <?php echo h($i); ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, array('page' => $total_pages))); ?>">
                                                <?php echo h($total_pages); ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, array('page' => $page + 1))); ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Next <i class="fas fa-chevron-right"></i></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>

                        <?php elseif ($result): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-3x mb-3"></i>
                                <h5>No data found</h5>
                                <p>No records match your selected filters.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportReport(format) {
    var urlParams = new URLSearchParams(window.location.search);
    urlParams.set('format', format);
    window.location.href = 'admin_reports.php?' + urlParams.toString();
}
</script>

<?php include __DIR__ . '/footer.php'; ?>