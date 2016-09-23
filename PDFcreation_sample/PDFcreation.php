<?php
//this page is used for report generation cron page
	include('ajax/database.php');
	require_once('html2_image/html2_pdf_lib/html2pdf.class.php');
	
	//creating base url
	$base_path=getcwd();
	
	$base_url  = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
	$base_url .= $_SERVER['SERVER_NAME'];
	$base_url .= $_SERVER['REQUEST_URI'];
	$base_url=dirname($base_url).'/';
	
	
	$cur_date 	= date('Y-m-d').' 00:00:00';
	$last_month 	= date('Y-m-d', strtotime(date('Y-m-d')." -1 month"));
	$month_name 	= date('F-Y', strtotime($last_month));
	$first_date 	= date('Y-m-01', strtotime($last_month));
	$last_date  	= date('Y-m-t', strtotime($last_month));
	
	$month 		= date('m', strtotime($last_month));
	$year 		= date('Y', strtotime($last_month));
	
	//echo 'Report generated of : '.$first_date.' to '.$last_date.'<br>';
	
	$total_array = array();
	
	$all_newspaper_query 	=   mysql_query( "SELECT * FROM global_vendor order by name asc");
	
	$i=$j=0;
	
	while($result = mysql_fetch_array($all_newspaper_query)){
		
		
		$total_array[$i]['id'] 		= $result['id'];
		$total_array[$i]['name'] 	= $result['name'];
		$total_array[$i]['all_sms'] 	= array();
		
		$report_month_sql 	= 	"SELECT  `gv` . name as newspaper_name ,  `gu` . * , DATE_SUB( gu.send_date, INTERVAL 30 
							DAY ) AS sending_date
							FROM  `global_vendor` AS  `gv` ,  `guest_user` AS  `gu` 
							WHERE gv.id = gu.global_vd_id
							AND gu.global_vd_id = '".$result['id']."'
							AND gu.send_date
							BETWEEN  '".$first_date.' 00:00:00'."'
							AND  '".$last_date.' 00:00:00'."'
							ORDER BY  `gu`.`send_date` asc";
		
		$total_array[$i]['all_sms_q'] = $report_month_sql;
				
		$report_month_qry = mysql_query($report_month_sql) or die(mysql_error());
		$j1 = 1;
		while($result1 = mysql_fetch_array($report_month_qry)){
			//echo '<pre>'; print_r($result); echo '</pre>';
			
			$age_details = mysql_fetch_array(mysql_query('SELECT *  FROM ages WHERE id = '.$result1['ages']));
			
			$total_array[$i]['all_sms'][$j]['id'] 		= $j1;
			//$total_array[$i]['all_sms'][$j]['name'] 	= $result1['id'];
			$total_array[$i]['all_sms'][$j]['mobile'] 	= $result1['mobile'];
			//$total_array[$i]['all_sms'][$j]['send_date'] = $result1['send_date'];
			$total_array[$i]['all_sms'][$j]['send_date'] = $result1['send_date'];
			$total_array[$i]['all_sms'][$j]['name'] 	= $result1['name'];
			$total_array[$i]['all_sms'][$j]['gender'] 	= ($result1['gender'] == 'F') ? 'Female' : 'Male';
			$total_array[$i]['all_sms'][$j]['ages'] 	= (isset($age_details['range'])) ? $age_details['range'] : $result1['ages'];
			$total_array[$i]['all_sms'][$j]['zip_code'] 	= $result1['zip_code'];
			
			if($result1['download'] == 'A')
												$download = 'G';
											elseif($result1['download'] == 'I')
												$download = 'A';
			$total_array[$i]['all_sms'][$j]['download'] 	= $download;
			$total_array[$i]['all_sms'][$j]['amount'] 	= $result1['amount'];
			
			$j1++;
			
			$j++;
		}
		
		$i++;
	}
	
	//echo '<pre>'; print_r($total_array); echo '</pre>';
	
	$search = array(' ', "'", '"'); $replace = array('_', '_', '_');
	
	foreach ($total_array as $records)
	{
		if(count($records['all_sms']) > 0)
		{
			//$csv = "col1, col2, col3, col4, col5, col6, col7 \n";  //Column headers
			
			$fname 	= str_replace($search, $replace, $records['name']);
			
			$csv = "Serial No, Name, Phone, Gender, Age, Zip Code, Download, Date ,Gift Amount \n";  //Column headers
			foreach ($records['all_sms'] as $k=>$record){
				$csv .= $record['id'].','.$record['name'].','.$record['mobile'].','.$record['gender'].','.$record['ages'].','.$record['zip_code'].','.$record['download'].','.date('Y-m-d', strtotime($record['send_date'])).','.$record['amount']."\n";
			}
			
			 $csv_name = __DIR__.'/assets/Reports/Newspaper_report/'.$month_name.'-'.$fname.'-report.csv';
			 
			 $pdf_name = __DIR__.'/assets/Reports/Newspaper_report/'.$month_name.'-'.$fname.'-report.pdf';
			//echo "<br>";
			
			if (file_exists($csv_name)) 
				@unlink($csv_name);
			
			$csv_handler = fopen ($csv_name, 'wb');
			
			if(fwrite ($csv_handler, $csv)){
				echo '<br>Csv created of <a href="assets/Reports/Newspaper_report/'.$month_name.'-'.$fname.'-report.csv" target="_blank">'.$fname.'</a><br>';
				
				$check_for_data = mysql_num_rows(mysql_query('SELECT * FROM report_history WHERE vendor_id = "'.$records['id'].'" AND month = "'.$month.'" AND year = "'.$year.'"'));
				if($check_for_data == 0)
				{
					mysql_query('INSERT INTO report_history SET vendor_id = "'.$records['id'].'", month = "'.$month.'", year = "'.$year.'", csv_name = "'.$month_name.'-'.$fname.'-report.csv", status = 1');
				}else
				{
					mysql_query('Update report_history SET csv_name = "'.$month_name.'-'.$fname.'-report.csv" where vendor_id='.$records['id']);	
				}
			}
			else
				echo '<br>Failed to create csv of: '.$fname.'<br><br>';
				
			fclose ($csv_handler);
			
			
			$html = file_get_contents($base_url.'demo_html.php?news_id='.$records['id']);
			ob_start();
			echo $html;
			$content = ob_get_clean();
			ob_clean ();
			
			$html2pdf = new HTML2PDF('P', 'A4', 'en');
			//      $html2pdf->setModeDebug();
			$html2pdf->setDefaultFont('helvetica');
			$html2pdf->writeHTML($content);
			$file = $html2pdf->Output($pdf_name,'F');
			
			$check_for_data_pdf = mysql_num_rows(mysql_query('SELECT * FROM report_history WHERE vendor_id = "'.$records['id'].'" AND month = "'.$month.'" AND year = "'.$year.'"'));
			if($check_for_data_pdf == 0)
			{
				mysql_query('INSERT INTO report_history SET vendor_id = "'.$records['id'].'", month = "'.$month.'", year = "'.$year.'", pdf_name = "'.$month_name.'-'.$fname.'-report.pdf", status = 1');
			}else
			{
				mysql_query('Update report_history SET pdf_name = "'.$month_name.'-'.$fname.'-report.pdf" where vendor_id='.$records['id']);	
			}
			
			
		}
	}
?>	