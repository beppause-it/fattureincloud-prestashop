# FattureInCloud Prestashop Module
@author    Websuvius di Michele Matto <michele@websuvius.it>
@copyright FattureInCloud - Madbit Entertainment S.r.l.
@license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)

## Corrispetivi (Receipt) - v2.3.0

Il modulo può ora creare anche **corrispettivi (scontrini)** su FattureInCloud.

### Configurazione
- **Crea fatture**: crea fatture su FattureInCloud
- **Crea corrispettivi**: crea corrispettivi (Receipt) su FattureInCloud

Se entrambe le opzioni sono attive, viene applicata la regola:
- se l'indirizzo di fatturazione dell'ordine ha **Partita IVA** (`vat_number`) valorizzata → **FATTURA**
- altrimenti → **CORRISPETTIVO**

### Nota autorizzazioni (OAuth scope)
Per gestire i corrispettivi viene richiesto lo scope `receipts:a`. Dopo l'aggiornamento del modulo potrebbe essere necessario **riconnettere** il modulo (device code) per concedere il nuovo permesso.
