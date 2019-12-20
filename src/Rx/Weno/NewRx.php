<?php


namespace OpenEMR\Rx\Weno;


use SimpleXMLElement;
use OpenEMR\Rx\Weno\PharmaciesController;
use Pharmacy;

class NewRx
{



    public function creatOrderXMLBody($list)
    {

        if (substr_count($list, ",") > 1) {
            $prescriptions = explode(",", $list);
        } else {
            $prescriptions = $list;
        }

        $messageid = rand().rand();
        $wenoid = 157;
        $pharmacyid  = '0127781';
        $prescribernpi1 = '1902820954';
        $partnerID = '9039730E79C1167765840EDABC6AB2A6';

        $envelope = '<Message xmlns:xsi=":-https://wexlb.wenoexchange.com/schema/POSTRX"' .
                    ' DatatypesVersion="20170715" TransportVersion="20170715" ' .
                    ' TransactionDomain="SCRIPT" TransactionVersion="20170715" ' .
                    ' StructuresVersion="20170715" ECLVersion="20170715"> ' .
                    '</Message>';

        $xml = new SimpleXMLElement($envelope);
        $pharmacyInfo = new Pharmacy($pharmacyid);

        $mHeader = $xml->addChild('Header');
        $to = $mHeader->addChild('To', $pharmacyid);
        $to->addAttribute('Qualifier', 'P');
        $from = $mHeader->addChild('From', $prescribernpi1);
        $from->addAttribute('Qualifier', 'C');
        $mHeader->addChild('MessageID', 'jse'.$messageid);
        $mHeader->addChild('SentTime', date('Y-m-d')."T".date('H:i:s'));

        $security = $mHeader->addchild('Security');
        $username = $security->addChild('UsernameToken');
        $username->addchild('Username', $wenoid);
        $pass = $username->addChild('Password', $partnerID);
        $pass->addAttribute('Type', 'PasswordDigest');
        $sender = $security->addChild('Sender');
        $sender->addChild('SecondaryIdentification', 'doright@any.com');

        $software = $mHeader->addChild('SenderSoftware');
        $software->addChild('SenderSoftwareDeveloper', 'Sherwin Gaddis');
        $software->addChild('SenderSoftwareProduct', 'OpenEMR v5.0.2');
        $software->addChild('SenderSoftwareVersionRelease', '2.0.1');

        $signature = $mHeader->addChild('DigitalSignature');
        $signature->addAttribute('Version', 'T');
        $indicator = $signature->addChild('DigitalSignatureIndicator', '1');

        $mBody = $xml->addChild('Body');
        $newrx = $mBody->addChild('NewRx');
        $newrx->addChild('ReturnReceipt', '001');
        $allergy = $newrx->addChild('AllergyOrAdverseEvent');
        $allergy->addChild('NoKnownAllergies', 'Y');

        $benefits = $newrx->addChild('BenefitsCoordination');
        $payer = $benefits->addChild('PayerIdentification');
        $processor = $payer->addChild('ProcessorIdentificationNumber', 'HT');
        $iinn = $payer->addChild('IINNumber', '015284');
        $cardid = $benefits->addChild('CardholderID', 'WENO');
        $groupid = $benefits->addChild('GroupID', 'BSURE');

        $patient = $newrx->addChild('Patient');
        $human = $patient->addChild('HumanPatient');
        $name = $human->addChild('Name');
        $name->addChild('LastName', 'Doe');
        $name->addChild('FirstName', 'Jane');
        $gender = $human->addChild('Gender', 'F');
        $dob = $human->addChild('DateOfBirth');
        $dob->addChild('Date', '2004-06-17');
        $address = $human->addChild('Address');
        $address->addChild('AddressLine1', 'HN 321, Main Road');
        $address->addChild('AddressLine2', 'Apt B-12');
        $address->addChild('City', 'Boston');
        $address->addChild('StateProvince', 'MA');
        $address->addChild('PostalCode', '09653');

        $address->addChild('CountryCode', 'US');
        $comm = $human->addChild('CommunicationNumbers');
        $phone = $comm->addChild('PrimaryTelephone');
        $phone->addChild('Number', '6018675309');
        $phone->addChild('SupportsSMS', 'N');

        $pharmacy = $newrx->addChild('Pharmacy');
        $identification = $pharmacy->addChild('Identification');
        $identification->addChild('NCPDPID', '7654321');
        $identification->addChild('NPI', '369852147');
        $specialty = $pharmacy->addChild('Specialty', 'Retail');
        $businessname = $pharmacy->addChild('BusinessName', 'Rexall Drugs');
        $addressbn = $pharmacy->addChild('Address');
        $addressbn->addChild('AddressLine1', '2200 24th Ave');
        $addressbn->addChild('AddressLine2', 'Ste 100');
        $addressbn->addChild('City', 'Meridian');
        $addressbn->addChild('StateProvince', 'MS');
        $addressbn->addChild('PostalCode', '39301');

        $commnum = $pharmacy->addChild('CommunicationNumbers');
        $priphone = $commnum->addChild('PrimaryTelephone');
        $priphone->addChild('Number', '6015551212');
        $faxnum = $commnum->addChild('Fax');
        $faxnum->addChild('Number', '6015571212');

        $prescriber = $newrx->addChild('Prescriber');
        $nonvet = $prescriber->addChild('NonVeterinarian');
        $nonvetid = $nonvet->addChild('Identification');
        $nonvetid->addChild('DEANumber', '748596');
        $nonvetid->addChild('NPI', '987654321');
        $prename = $nonvet->addChild('Name');
        $prename->addChild('LastName', 'Shaft');
        $prename->addChild('FirstName', 'John');
        $preAddress = $nonvet->addChild('Address');
        $preAddress->addChild('AddressLine1', '1910 25th Ave');
        $preAddress->addChild('City', 'Meridian');
        $preAddress->addChild('StateProvince', 'MS');
        $preAddress->addChild('PostalCode', '39301');
        $preAddress->addChild('CountryCode', 'US');
        $commnum2 = $nonvet->addChild('CommunicationNumbers');
        $priphone2 = $commnum2->addChild('PrimaryTelephone');
        $priphone2->addChild('Number', '6016935555');

        $observation = $newrx->addChild('Observation');
        $measurement = $observation->addChild('Measurement');
        $vitals = $measurement->addChild('VitalSign', 'Weight');
        $loincv = $measurement->addChild('LOINCVersion', '441');
        $values = $measurement->addChild('Value', '112');
        $unitof = $measurement->addChild('UnitOfMeasure', 'pounds');
        $ucumv = $measurement->addChild('UCUMVersion', 'string');
        $obdate = $measurement->addChild('ObservationDate');
        $obdate->addChild('DateTime', '2011-01-05T12:00:05.16');
        $measurement2 = $observation->addChild('Measurement');
        $vitals2 = $measurement2->addChild('VitalSign', 'Height');
        $loincv2 = $measurement2->addChild('LOINCVersion', '4415');
        $values2 = $measurement2->addChild('Value', '1125');
        $unitof2 = $measurement2->addChild('UnitOfMeasure', 'inches');
        $ucumv2 = $measurement2->addChild('UCUMVersion', 'string2');
        $obdate2 = $measurement2->addChild('ObservationDate');
        $obdate2->addChild('DateTime', '2011-01-05T12:00:05.16');
        $obnotes = $observation->addChild('ObservationNotes', 'patient is 62 inches in height and weighs 112 lbs');

        $medicationpres = $newrx->addChild('MedicationPrescribed');
        $description = $medicationpres->addChild('DrugDescription', 'Ambien 10 mg oral tablet');
        $drugcode = $medicationpres->addChild('DrugCoded');
        $dbcode = $drugcode->addChild('DrugDBCode');
        $dbcode->addChild('Code', '854875');
        $dbcode->addChild('Qualifier', 'SCD');
        $deaschedule = $drugcode->addChild('DEASchedule');
        $deaschedule->addChild('Code', 'C48677');
        $quantity = $medicationpres->addChild('Quantity');
        $quantity->addChild('Value', '50');
        $quantity->addChild('CodeListQualifier', '38');
        $quantityofunit = $quantity->addChild('QuantityUnitOfMeasure');
        $quantityofunit->addChild('Code', 'C48542');
        $writtendate = $medicationpres->addChild('WrittenDate');
        $writtendate->addChild('Date', '2019-07-19');
        $substitution = $medicationpres->addChild('Substitutions', '0');
        $refills = $medicationpres->addChild('NumberOfRefills', '0');
        $diagnosis = $medicationpres->addChild('Diagnosis');
        $clinical = $diagnosis->addChild('ClinicalInformationQualifier', '1');
        $primary = $diagnosis->addChild('Primary');
        $primary->addChild('Code', 'G47.00');
        $primary->addChild('Qualifier', 'ABF');
        $primary->addChild('Description', 'Insomnia');
        $note = $medicationpres->addChild('Note', 'Patient Rx Savings Card BIN: 015284; PCN: HT: Group: BSURE; ID: Weno');
        $sig = $medicationpres->addChild('Sig');
        $sig->addChild('SigText', 'Take one tablet before bedtime');

        return $xml->asXML();
    }




}

