<?php

namespace App\Traits;

use thiagoalessio\TesseractOCR\TesseractOCR;

trait ExtractPaymentInfo
{
    public function extractPaymentInfoFromImage($imagePath)
    {
        // Perform OCR on the image
        $ocrText = $this->performOCR($imagePath);

        // Extract Payment information from the OCR text
        $paymentInfo = $this->extractPaymentInfo($ocrText);


        return $paymentInfo;
    }

    private function performOCR($imagePath)
    {
        // Perform OCR on the image using TesseractOCR
        $ocr = new TesseractOCR($imagePath);
        return $ocr->run();
    }

    private function extractPaymentInfo($text)
    {
        // Preprocess the text to identify key phrases
        $keywords = ['Confirmed', 'Ksh', 'sent to', 'on'];

        // Initialize variables to store extracted information
        $confirmationNumber = null;
        $amount = null;
        $recipient = null;
        $time = null;

        // Find the position of the "Confirmed" keyword
        $confirmedPos = strpos($text, 'Confirmed');

        // Process based on the position of the "Confirmed" keyword
        if ($confirmedPos !== false) {
            // Extract confirmation number from the substring preceding the "Confirmed" keyword
            $confirmationNumber = trim(substr($text, 0, $confirmedPos));
        }

        // Iterate through each keyword to find its position in the text
        foreach ($keywords as $keyword) {
            // Find the position of the keyword in the text
            $pos = strpos($text, $keyword);

            // Process based on the keyword found
            switch ($keyword) {
                case 'Ksh':
                    // Extract amount
                    if ($pos !== false) {
                        // Extract the amount string
                        $amountString = strtok(substr($text, $pos), ' ');

                        // Replace comma with decimal point
                        $amountString = str_replace(',', '.', $amountString);

                        // Remove 'Ksh' and format the amount with two decimal places
                        $amount = number_format((float) str_replace('Ksh', '', $amountString), 2, '.', '');
                    }
                    break;

//                case 'sent to':
//                    // Extract recipient
//                    if ($pos !== false) {
//                        // The recipient is assumed to be the text after 'sent to' until the next keyword
//                        $recipient = trim(strtok(substr($text, $pos + strlen($keyword)), 'on'));
//                    }
//                    break;

                case 'on':
                    // Extract time information
                    if ($pos !== false) {
                        // Find the position of "23" to identify the year
                        $yearPos = strpos($text, '23', $pos);
                        // Extract the time string from the position of "23"
                        $timeString = trim(substr($text, $yearPos, 8)); // Extracting 15 characters starting from "23"
                        // Assign the time string directly
                        $time = $timeString;
                    }
                    break;


                default:
                    break;
            }
        }

        // Check if all required information is found
        if ($confirmationNumber !== null && $amount !== null && $time !== null) {
            // Create an array to hold the extracted Payment information
            $paymentInfo = [
                'confirmationNumber' => $confirmationNumber,
                'amount' => $amount,
                //'recipient' => $recipient ?? null,
                'time' => $time,
            ];

            return $paymentInfo;
        }

        // Return null if any required information is missing
        return null;
    }
    }
