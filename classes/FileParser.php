<?php
// File Parser Class - Extracts text from various formats

class FileParser
{

    // Extract text from PDF file
    public static function extractFromPDF($filepath)
    {
        try {
            // Using smalot/pdfparser library
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filepath);
            $text = $pdf->getText();

            // Clean up text
            $text = trim($text);

            if (empty($text) || strlen($text) < 10) {
                // Fallback or error if no text found
                throw new Exception('No readable text found in PDF. It might be an image-only PDF.');
            }

            return $text;

        } catch (Exception $e) {
            error_log("PDF extraction error: " . $e->getMessage());
            throw $e;
        }
    }


    // Extract text from DOCX file
    public static function extractFromDOCX($filepath)
    {
        try {
            // DOCX is a ZIP file containing XML documents
            $zip = new ZipArchive();

            if ($zip->open($filepath) !== true) {
                throw new Exception('Could not open DOCX file');
            }

            // The main document content is in word/document.xml
            $content = $zip->getFromName('word/document.xml');

            if ($content === false) {
                $zip->close();
                throw new Exception('Could not read document.xml from DOCX');
            }

            $zip->close();

            // Parse XML and extract text
            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOWARNING | LIBXML_NOERROR);

            if ($xml === false) {
                throw new Exception('Could not parse DOCX XML content');
            }

            // Register namespaces
            $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            // Extract all text nodes
            $textNodes = $xml->xpath('//w:t');
            $text = '';

            if ($textNodes) {
                foreach ($textNodes as $node) {
                    $text .= (string) $node . ' ';
                }
            }

            // Also try to extract from paragraphs
            if (empty(trim($text))) {
                $paragraphs = $xml->xpath('//w:p');
                if ($paragraphs) {
                    foreach ($paragraphs as $p) {
                        $pText = self::extractTextFromNode($p);
                        if (!empty($pText)) {
                            $text .= $pText . "\n";
                        }
                    }
                }
            }

            // Clean up
            $text = trim($text);

            if (empty($text)) {
                throw new Exception('No text could be extracted from DOCX file');
            }

            return $text;

        } catch (Exception $e) {
            error_log("DOCX extraction error: " . $e->getMessage());
            throw $e;
        }
    }

    // Extract text from XML node recursively
    private static function extractTextFromNode($node)
    {
        $text = '';

        if (isset($node->children()->t)) {
            foreach ($node->children()->t as $t) {
                $text .= (string) $t . ' ';
            }
        }

        // Recursively search children
        foreach ($node->children() as $child) {
            $childText = self::extractTextFromNode($child);
            if (!empty($childText)) {
                $text .= $childText;
            }
        }

        return $text;
    }

    // Extract text from image file using OCR
    public static function extractFromImage($filepath)
    {
        try {
            // Check if Tesseract OCR is available
            $tesseractPath = self::findTesseract();

            if ($tesseractPath === null) {
                throw new Exception('OCR (Tesseract) is not installed. To enable image text extraction, please install Tesseract OCR from https://github.com/UB-Mannheim/tesseract/wiki');
            }

            // Create temporary output file
            $outputBase = sys_get_temp_dir() . '/ocr_' . uniqid();
            $outputFile = $outputBase . '.txt';

            // Run Tesseract
            $command = sprintf(
                '"%s" "%s" "%s" 2>&1',
                $tesseractPath,
                $filepath,
                $outputBase
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($outputFile)) {
                throw new Exception('OCR processing failed: ' . implode(' ', $output));
            }

            // Read extracted text
            $text = file_get_contents($outputFile);

            // Cleanup
            if (file_exists($outputFile)) {
                unlink($outputFile);
            }

            if (empty(trim($text))) {
                throw new Exception('No text could be extracted from the image. The image might not contain readable text.');
            }

            return trim($text);

        } catch (Exception $e) {
            error_log("Image OCR error: " . $e->getMessage());
            throw $e;
        }
    }

    // Find Tesseract executable
    private static function findTesseract()
    {
        // Common Tesseract installation paths on Windows
        $windowsPaths = [
            'C:\Program Files\Tesseract-OCR\tesseract.exe',
            'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe',
            getenv('PROGRAMFILES') . '\Tesseract-OCR\tesseract.exe',
            getenv('PROGRAMFILES(X86)') . '\Tesseract-OCR\tesseract.exe',
        ];

        foreach ($windowsPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try system PATH
        exec('where tesseract 2>&1', $output, $returnCode);
        if ($returnCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        // Unix/Linux paths
        $unixPaths = ['/usr/bin/tesseract', '/usr/local/bin/tesseract'];
        foreach ($unixPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    // Extract text from any supported file type
    public static function extractText($filepath, $mimeType)
    {
        switch ($mimeType) {
            case 'text/plain':
                $text = file_get_contents($filepath);
                if ($text === false) {
                    throw new Exception('Failed to read text file');
                }
                return $text;

            case 'application/pdf':
                return self::extractFromPDF($filepath);

            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return self::extractFromDOCX($filepath);

            case 'image/jpeg':
            case 'image/jpg':
            case 'image/png':
            case 'image/gif':
            case 'image/bmp':
            case 'image/tiff':
                return self::extractFromImage($filepath);

            default:
                throw new Exception('Unsupported file type: ' . $mimeType);
        }
    }
}
