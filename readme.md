# Adatbázis Alkalmazás

## Szükséges Beállítások

Az oldal működéséhez szükség van XAMPP-re a HTDOCS mappába kell kicsomagolni a mappát és az apache szerver futtatásával lehet elindítani az alkalmazást.

Az adatbázis DOCKER alapján fut 1521-es porton és a működéshez szükség van létrehozni az alábbi felhasználói fiókot: 
### Felhasználói Fiók
- **Felhasználónév:** `ATTILA`
- **Jelszó:** `Attila`

### Adatbázis Beállítások
- **Port:** `1521`
- **Adatbázis Szolgáltatás Neve (SERVICE_NAME):** `XE`

### SQL Developer Beállítások
1. Nyisd meg az SQL Developert.
2. Hozz létre egy új kapcsolatot az alábbi adatokkal:
   - **Felhasználónév:** `ATTILA`
   - **Jelszó:** `Attila`
   - **Host:** `localhost`
   - **Port:** `1521`
   - **SID vagy Service Name:** `XE`
3. Teszteld a kapcsolatot, majd mentsd el.

4. Adatbázis importálása a `webhoszting.sql` file-ból

### Youtube segédlet: 

- **Videó:** https://www.youtube.com/watch?v=0kdIDLxJFIE