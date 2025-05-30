-- Triggerek --
CREATE OR REPLACE TRIGGER NotifyVpsExpiration
AFTER INSERT OR UPDATE ON ATTILA.SUBSCRIPTION
FOR EACH ROW
WHEN (NEW.SERVICE_ID IS NOT NULL AND NEW.END_DATE - SYSDATE <= 7 AND NEW.STATUS = 'Aktív')
DECLARE
    v_service_type VARCHAR2(50);
BEGIN
    SELECT SERVICE_TYPE INTO v_service_type
    FROM ATTILA.SERVICE
    WHERE ID = :NEW.SERVICE_ID;

    IF v_service_type = 'VPS' THEN
        INSERT INTO ATTILA.NOTIFICATIONS (USER_ID, MESSAGE)
        VALUES (:NEW.USER_ID, 'Az Ön VPS előfizetése hamarosan lejár. Kérjük, hosszabbítsa meg!');
    END IF;
END;
/

CREATE OR REPLACE TRIGGER DeleteNotificationsOnVpsDelete
AFTER DELETE ON ATTILA.VPS
FOR EACH ROW
BEGIN
    DELETE FROM ATTILA.NOTIFICATIONS
    WHERE USER_ID IN (
        SELECT USER_ID
        FROM ATTILA.SUBSCRIPTION s
        JOIN ATTILA.SERVICE srv ON s.SERVICE_ID = srv.ID
        WHERE srv.VPS_ID = :OLD.ID
    );

    DELETE FROM ATTILA.SUBSCRIPTION
    WHERE SERVICE_ID IN (
        SELECT ID
        FROM ATTILA.SERVICE
        WHERE VPS_ID = :OLD.ID
    );
END;
/


-- aktív előfizetések lekérdezése --
CREATE OR REPLACE PROCEDURE GetActiveSubscriptions (
    p_user_id IN NUMBER
)
IS
BEGIN
    FOR rec IN (
        SELECT s.ID AS SUBSCRIPTION_ID, srv.SERVICE_TYPE, s.END_DATE
        FROM ATTILA.SUBSCRIPTION s
        JOIN ATTILA.SERVICE srv ON s.SERVICE_ID = srv.ID
        WHERE s.USER_ID = p_user_id AND s.STATUS = 'Aktív'
    )
    LOOP
        DBMS_OUTPUT.PUT_LINE('Előfizetés ID: ' || rec.SUBSCRIPTION_ID || 
                             ', Szolgáltatás: ' || rec.SERVICE_TYPE || 
                             ', Lejárat: ' || TO_CHAR(rec.END_DATE, 'YYYY-MM-DD'));
    END LOOP;
END;
/


CREATE OR REPLACE TRIGGER NotifyWebstorageExpiration
AFTER INSERT OR UPDATE ON ATTILA.SUBSCRIPTION
FOR EACH ROW
WHEN (NEW.SERVICE_ID IS NOT NULL AND NEW.END_DATE - SYSDATE <= 7 AND NEW.STATUS = 'Aktív')
DECLARE
    v_service_type VARCHAR2(50);
BEGIN
    SELECT SERVICE_TYPE INTO v_service_type
    FROM ATTILA.SERVICE
    WHERE ID = :NEW.SERVICE_ID;

    IF v_service_type = 'Webstorage' THEN
        INSERT INTO ATTILA.NOTIFICATIONS (USER_ID, MESSAGE, SUBSCRIPTION_ID)
        VALUES (:NEW.USER_ID, 'Az Ön Webstorage előfizetése hamarosan lejár. Kérjük, hosszabbítsa meg!', :NEW.ID);
    END IF;
END;
/

create or replace TRIGGER DeleteNotifyOnWebstorage
AFTER DELETE ON ATTILA.SERVICE
FOR EACH ROW
BEGIN
    DELETE FROM ATTILA.NOTIFICATIONS
    WHERE USER_ID IN (
        SELECT USER_ID
        FROM ATTILA.SUBSCRIPTION s
        JOIN ATTILA.SERVICE srv ON s.SERVICE_ID = srv.ID
        WHERE srv.WEBSTORAGE_ID = :OLD.ID
    );

    DELETE FROM ATTILA.SUBSCRIPTION
    WHERE SERVICE_ID IN (
        SELECT ID
        FROM ATTILA.SERVICE
        WHERE WEBSTORAGE_ID = :OLD.ID
    );
END;



create or replace PROCEDURE get_user_services(
    p_user_id IN NUMBER,
    p_cursor OUT SYS_REFCURSOR
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT sub.ID AS subscription_id,
               srv.SERVICE_TYPE,
               srv.PRICE,
               sub.START_DATE,
               sub.END_DATE,
               sub.STATUS
        FROM ATTILA.SUBSCRIPTION sub
        JOIN ATTILA.SERVICE srv ON sub.SERVICE_ID = srv.ID
        WHERE sub.USER_ID = p_user_id;
END;
/