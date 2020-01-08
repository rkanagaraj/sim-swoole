CREATE DEFINER=`root`@`%` PROCEDURE `list32`(IN plogusrid INT,IN pstatus INT,OUT pstatr VARCHAR(15000))
BEGIN
SET @rno=0;
if pstatus ="2" then
	SET @whr = CONCAT("(P.created_by = ",plogusrid," or 
P.team_member= ",plogusrid," or FIND_IN_SET( ",plogusrid,",P.task_assigned))  and 
(DATE_FORMAT(CC.dateof_followup,'%Y-%m-%d') <= DATE_FORMAT(now(),'%Y-%m-%d') OR 
DATE_FORMAT(CC.dateof_followup,'%Y-%m-%d') IS NULL) ");
    SET @status = " P.task_status =1 ";
    /*SET @Sort = "CC.pyt desc, p.task_status desc, CC.dateof_followup asc;";*/
    SET @Sort = "12 desc, 5 asc;";
elseif pstatus ="4" then
	SET @whr = CONCAT("(P.created_by = ",plogusrid," or 
P.team_member= ",plogusrid," or FIND_IN_SET( ",plogusrid,",P.task_assigned)) ");
    SET @status = " P.task_status =1 ";
    SET @Sort = "CC.pyt desc, P.task_name asc, CC.dateof_followup asc;";
elseif pstatus = "0" then
	SET @whr = CONCAT("(P.created_by = ",plogusrid," or 
P.team_member= ",plogusrid," or FIND_IN_SET( ",plogusrid,",P.task_assigned)) ");
    SET @status  = " P.task_status !=0 ";
    SET @Sort = "CC.pyt desc, p.task_name asc, CC.dateof_followup asc;";
elseif pstatus = "1" then
	SET @whr = CONCAT("(P.created_by = ",plogusrid," or 
P.team_member= ",plogusrid," or FIND_IN_SET(",plogusrid,",P.task_assigned)) ");
    SET @status  = " P.task_status =2 ";
    SET @Sort = "CC.pyt desc, p.task_status desc, CC.dateof_followup asc;";
else
	SET @whr = " 1=1 " ;
    SET @status  =" P.task_status =3 ";
    SET @Sort = "CC.pyt desc, p.task_status desc, CC.dateof_followup asc;";
end if;


SET @pstatr = CONCAT('select p.id,cl.name as OtherName,P.task_name,

if(CC.alarm=0,DATE_FORMAT(CC.dateof_followup,''%a %e-%b''),DATE_FORMAT(CC.dateof_followup,''%a %e-%b %l:%i %p'')) as date_followup_update,
if(DATE_FORMAT(CC.dateof_followup,''%Y-%m-%d'') IS NULL,DATE_FORMAT(''1971-01-01'',''%Y%m%d %H:%i''),
DATE_FORMAT(CC.dateof_followup,''%Y%m%d %H:%i'')) as date_followup_update1,
 us.name as TM, (SELECT group_concat('' '',name) FROM calmet_users WHERE FIND_IN_SET(id, task_assigned) ORDER BY 1 asc) as tms,
 IF(P.tasK_status=1,''Active'',if(P.tasK_status=3,''Completed'',''Open'')) as Status, 
 
if((select count(tf.meet_date)
from calmet_meeting cm
inner join calmet_task_meetings tf on cm.id = tf.meetid 
left outer join meet_det md on md.id = tf.meetid
left outer join calmet_users u on u.id = tf.loginid
where  tf.task_id = p.id and  concat(DATE_FORMAT(tf.meet_date,''%Y-%m-%d''),'' '',cm.meet_recu_etime) > now()
and FIND_IN_SET(',plogusrid,',cm.meet_tms) order by concat(tf.meet_date,'' '',cm.meet_recu_etime) asc limit 1)=0,
(select DATE_FORMAT(tf.meet_date,''%a %d-%b'')
from calmet_meeting cm
inner join calmet_task_meetings tf on cm.id = tf.meetid 
left outer join meet_det md on md.id = tf.meetid
left outer join calmet_users u on u.id = tf.loginid
where  tf.task_id = p.id and  concat(DATE_FORMAT(tf.meet_date,''%Y-%m-%d''),'' '',cm.meet_recu_etime) < now()
and FIND_IN_SET(',plogusrid,',cm.meet_tms) order by concat(tf.meet_date,'' '',cm.meet_recu_etime) desc limit 1),
(select DATE_FORMAT(tf.meet_date,''%a %d-%b'')
from calmet_meeting cm
inner join calmet_task_meetings tf on cm.id = tf.meetid 
left outer join meet_det md on md.id = tf.meetid
left outer join calmet_users u on u.id = tf.loginid
where  tf.task_id = p.id and  concat(DATE_FORMAT(tf.meet_date,''%Y-%m-%d''),'' '',cm.meet_recu_etime) > now()
and FIND_IN_SET(',plogusrid,',cm.meet_tms) order by concat(tf.meet_date,'' '',cm.meet_recu_etime) asc limit 1)) as Meet_date,
if((select count(tf.meet_date)
from calmet_meeting cm
inner join calmet_task_meetings tf on cm.id = tf.meetid 
left outer join meet_det md on md.id = tf.meetid
left outer join calmet_users u on u.id = tf.loginid
where  tf.task_id = p.id and  concat(DATE_FORMAT(tf.meet_date,''%Y-%m-%d''),'' '',cm.meet_recu_etime) > now()
and FIND_IN_SET(',plogusrid,',cm.meet_tms) order by concat(tf.meet_date,'' '',cm.meet_recu_etime) asc limit 1)=0,
(select DATE_FORMAT(tf.meet_date,''%Y%m%d'')
from calmet_meeting cm
inner join calmet_task_meetings tf on cm.id = tf.meetid 
left outer join meet_det md on md.id = tf.meetid
left outer join calmet_users u on u.id = tf.loginid
where  tf.task_id = p.id and  concat(DATE_FORMAT(tf.meet_date,''%Y-%m-%d''),'' '',cm.meet_recu_etime) < now()
and FIND_IN_SET(',plogusrid,',cm.meet_tms) order by concat(tf.meet_date,'' '',cm.meet_recu_etime) desc limit 1),
(select DATE_FORMAT(tf.meet_date,''%Y%m%d'')
from calmet_meeting cm
inner join calmet_task_meetings tf on cm.id = tf.meetid 
left outer join meet_det md on md.id = tf.meetid
left outer join calmet_users u on u.id = tf.loginid
where  tf.task_id = p.id and  concat(DATE_FORMAT(tf.meet_date,''%Y-%m-%d''),'' '',cm.meet_recu_etime) > now()
and FIND_IN_SET(',plogusrid,',cm.meet_tms) order by concat(tf.meet_date,'' '',cm.meet_recu_etime) asc limit 1)) as Meet_date1,


if((select Count(CONCAT_WS('' - '',u.ini,md.meet_code,md.meet_desc,md.tms))
from calmet_meeting cm
inner join calmet_task_meetings tf on cm.id = tf.meetid 
left outer join meet_det md on md.id = tf.meetid
left outer join calmet_users u on u.id = tf.loginid
where tf.task_id = p.id and concat(DATE_FORMAT(tf.meet_date,''%Y-%m-%d''),'' '',cm.meet_recu_etime) > now()
and FIND_IN_SET(',plogusrid,',cm.meet_tms) order by concat(tf.meet_date,'' '',cm.meet_recu_etime) asc limit 1)=0,
(select CONCAT_WS('' - '',u.ini,md.meet_code,md.meet_desc,md.tms)
from calmet_meeting cm
inner join calmet_task_meetings tf on cm.id = tf.meetid 
left outer join meet_det md on md.id = tf.meetid
left outer join calmet_users u on u.id = tf.loginid
where tf.task_id = p.id and concat(DATE_FORMAT(tf.meet_date,''%Y-%m-%d''),'' '',cm.meet_recu_etime) < now()
and FIND_IN_SET(',plogusrid,',cm.meet_tms) order by concat(tf.meet_date,'' '',cm.meet_recu_etime) desc limit 1),
(select CONCAT_WS('' - '',u.ini,md.meet_code,md.meet_desc,md.tms)
from calmet_meeting cm
inner join calmet_task_meetings tf on cm.id = tf.meetid 
left outer join meet_det md on md.id = tf.meetid
left outer join calmet_users u on u.id = tf.loginid
where tf.task_id = p.id and concat(DATE_FORMAT(tf.meet_date,''%Y-%m-%d''),'' '',cm.meet_recu_etime) > now()
and FIND_IN_SET(',plogusrid,',cm.meet_tms) order by concat(tf.meet_date,'' '',cm.meet_recu_etime) asc limit 1)) as Meet_code,

if(CC.pyt IS NULL,4,CC.pyt) as pyt,
DATE_FORMAT(p.complete_actual,''%d %b %y'') as comp_date,
(select count(r.pt_id) from calmet_tasks r  where r.pt_id = p.id) as st, (@rno:=@rno+1) as sno
from calmet_tasks P 
inner join categorylist cl on (p.relate_to = cl.type) and (p.sel_relateid = cl.id) 
LEFT OUTER JOIN calmet_users US ON(US.id=P.team_member) 
left outer join calmet_companies cm on p.sel_relateid = cm.id 
LEFT OUTER JOIN calmet_task_followup_dates CC ON (CC.task_id=P.id and (CC.loginid= ',plogusrid,')) 
left outer join meet_det mm on p.meet_id = mm.id where 1=1 and ', @whr ,' and ',@status ,' 
order by ',@sort);
SET pstatr = @pstatr;
PREPARE stmt FROM @pstatr;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
END