<?php
// Exportacoes dos pares filtrados. Executado depois da consulta e da ordenacao.
// Nao instala dependencias nem altera os dados do SQLite.
$exportFormat=(string)($_GET['exportar']??'');
if(!in_array($exportFormat,['pdf','xlsx'],true)||$setup||!loggedAdmin())return;

$exportRows=[];
foreach($pares as $p){
    $exportRows[]=[
        date('d/m/Y',strtotime($p['data'])),
        (string)$p['nome'],
        (string)$p['empresa'],
        (string)$p['tipo_acesso'],
        (string)($p['entrada']??''),
        (string)($p['saida']??'')
    ];
}
$headings=['Data','Pessoa','Empresa','Tipo de acesso','Entrada','Saída'];
$stamp=date('Ymd_His');

function exportXml($value):string{
    // Remove caracteres de controle proibidos por XML 1.0.
    $value=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u','',(string)$value)??'';
    return htmlspecialchars($value,ENT_XML1|ENT_QUOTES,'UTF-8');
}

function exportZipStore(array $files):string{
    // Writer ZIP sem extensao PHP zip: XLSX compativel com Office e LibreOffice.
    $body='';$directory='';$offset=0;$count=0;
    foreach($files as $name=>$content){
        $name=(string)$name;$content=(string)$content;
        $crc=crc32($content);$size=strlen($content);$nl=strlen($name);
        $local=pack('VvvvvvVVVvv',0x04034b50,20,0,0,0,0,$crc,$size,$size,$nl,0).$name.$content;
        $directory.=pack('VvvvvvvVVVvvvvvVV',0x02014b50,20,20,0,0,0,0,$crc,$size,$size,$nl,0,0,0,0,0,$offset).$name;
        $body.=$local;$offset+=strlen($local);$count++;
    }
    return $body.$directory.pack('VvvvvVVv',0x06054b50,0,0,$count,$count,strlen($directory),strlen($body),0);
}

if($exportFormat==='xlsx'){
    $sheetRows=array_merge([$headings],$exportRows);
    $sheet='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"/></sheetViews>'.
        '<cols><col min="1" max="1" width="14" customWidth="1"/><col min="2" max="2" width="32" customWidth="1"/><col min="3" max="3" width="27" customWidth="1"/><col min="4" max="4" width="20" customWidth="1"/><col min="5" max="6" width="14" customWidth="1"/></cols><sheetData>';
    foreach($sheetRows as $i=>$values){
        $nr=$i+1;$sheet.='<row r="'.$nr.'">';
        foreach($values as $j=>$val){
            // Texto literal (inlineStr) impede que nomes iniciados por = + - @ sejam formulas.
            $cell=chr(65+$j).$nr;
            $sheet.='<c r="'.$cell.'" t="inlineStr"><is><t xml:space="preserve">'.exportXml($val).'</t></is></c>';
        }
        $sheet.='</row>';
    }
    $sheet.='</sheetData><autoFilter ref="A1:F'.count($sheetRows).'"/></worksheet>';
    $files=[
        '[Content_Types].xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
        '_rels/.rels'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
        'xl/workbook.xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Movimentacoes" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
        'xl/worksheets/sheet1.xml'=>$sheet
    ];
    $data=exportZipStore($files);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="movimentacoes_portaria_'.$stamp.'.xlsx"');
    header('Content-Length: '.strlen($data));
    echo $data;exit;
}

// PDF A4 paisagem, paginado; fonte padrao Helvetica e codificacao Windows-1252.
function pdfSafe($value,int $max):string{
    $value=trim(preg_replace('/\s+/u',' ',(string)$value)??'');
    if(function_exists('mb_strimwidth'))$value=mb_strimwidth($value,0,$max,'...', 'UTF-8');
    else $value=substr($value,0,$max);
    if(function_exists('iconv'))$value=iconv('UTF-8','Windows-1252//TRANSLIT',$value)?:'';
    else $value=preg_replace('/[^\x20-\x7E]/','?', $value);
    return str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$value);
}
function pdfText(float $x,float $y,string $s,int $size=9):string{
    return 'BT /F1 '.$size.' Tf '.sprintf('%.2F %.2F Td',$x,$y).' ('.pdfSafe($s,500).') Tj ET' . "\n";
}
$pageWidth=842;$pageHeight=595;$rowsPerPage=31;
$batches=array_chunk($exportRows,$rowsPerPage);
if(!$batches)$batches=[[]];
$objects=[];$objects[1]='<< /Type /Catalog /Pages 2 0 R >>';
$objects[3]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
$pageIds=[];$totalPages=count($batches);
foreach($batches as $pageNo=>$batch){
    $content='0.12 0.18 0.28 rg 0 553 842 42 re f' . "\n";
    $content.='1 1 1 rg '.pdfText(28,568,'PORTARIA FACIAL - RELATORIO DE MOVIMENTACOES',15);
    $content.='0 0 0 rg '.pdfText(28,538,'Emitido em '.date('d/m/Y H:i').'   |   Registros: '.count($exportRows).'   |   Pagina '.($pageNo+1).' de '.$totalPages,9);
    $x=[28,102,294,479,630,725];
    $maxChars=[12,30,28,21,12,12];
    $content.='0.90 0.93 0.97 rg 24 505 792 22 re f' . "\n";
    $content.='0 0 0 rg';
    foreach($headings as $j=>$label)$content.=pdfText($x[$j],512,$label,9);
    foreach($batch as $i=>$row){
        $y=490-$i*15;
        if($i%2===1)$content.='0.96 0.97 0.99 rg 24 '.($y-4).' 792 15 re f 0 0 0 rg' . "\n";
        foreach($row as $j=>$value)$content.=pdfText($x[$j],$y,(function_exists('mb_strimwidth')?mb_strimwidth((string)$value,0,$maxChars[$j],'...','UTF-8'):substr((string)$value,0,$maxChars[$j])),8);
    }
    $content.='0.35 0.40 0.47 rg '.pdfText(28,22,'Documento gerado automaticamente pela Portaria Facial.',8);
    $pageId=4+$pageNo*2;$streamId=$pageId+1;
    $pageIds[]=$pageId;
    $objects[$pageId]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$streamId.' 0 R >>';
    $objects[$streamId]='<< /Length '.strlen($content).' >>' . "\nstream\n".$content."endstream";
}
$objects[2]='<< /Type /Pages /Kids ['.implode(' ',array_map(fn($n)=>$n.' 0 R',$pageIds)).'] /Count '.count($pageIds).' >>';
ksort($objects);
$pdf="%PDF-1.4\n";$offsets=[0=>0];
foreach($objects as $id=>$obj){$offsets[$id]=strlen($pdf);$pdf.=$id." 0 obj\n".$obj."\nendobj\n";}
$xrefOffset=strlen($pdf);
$pdf.="xref\n0 ".(max(array_keys($objects))+1)."\n0000000000 65535 f \n";
for($i=1;$i<=max(array_keys($objects));$i++)$pdf.=sprintf('%010d 00000 n ' . "\n",$offsets[$i]??0);
$pdf.='trailer << /Size '.(max(array_keys($objects))+1).' /Root 1 0 R >>'."\nstartxref\n".$xrefOffset."\n%%EOF";
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="movimentacoes_portaria_'.$stamp.'.pdf"');
header('Content-Length: '.strlen($pdf));
echo $pdf;exit;
