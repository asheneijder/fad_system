<x-mail::message>
# Asset Assignment - Action Required
# Penyerahan Aset - Tindakan Diperlukan

**To / Kepada:** {{ $user->name }}  
**Assigned By / Diserahkan Oleh:** {{ $assignedBy->name }}

You have been assigned responsibility for the following asset:  
Anda telah diberikan tanggungjawab untuk aset berikut:

## Asset Details / Butiran Aset
- **Name / Nama:** {{ $asset->asset_name }}
- **Tag No. / No. Tag:** {{ $asset->asset_tag_no }}
- **Serial No. / No. Siri:** {{ $asset->serial_no }}
- **Condition / Keadaan:** {{ $assignment->condition_assigned }}

Please click the button below to confirm and accept responsibility:  
Sila klik butang di bawah untuk mengesahkan dan menerima tanggungjawab:

<x-mail::button :url="$confirmationUrl">
✅ Confirm & Accept Responsibility / Sahkan & Terima Tanggungjawab
</x-mail::button>

**This link will expire in 7 days**  
**Pautan ini akan tamat dalam 7 hari**

<small>
*This is an auto-generated email. Please do not reply.*  
*Email ini dihasilkan secara automatik. Jangan balas email ini.*
</small>

</x-mail::message>