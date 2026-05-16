"""SQL query templates parameterised by table prefix.

Compiled once at startup. All read-only queries against the osTicket schema.
The first-response time is derived from the earliest `thread_entry` of type
'R' (staff reply) attached to the ticket's thread.
"""

from dataclasses import dataclass


@dataclass(frozen=True)
class Queries:
    tickets_in_range: str
    statuses: str
    staff: str
    departments: str

    @classmethod
    def build(cls, prefix: str) -> "Queries":
        p = prefix
        tickets_in_range = f"""
            SELECT
                t.ticket_id,
                t.number,
                t.status_id,
                t.dept_id,
                t.staff_id,
                t.isoverdue,
                t.isanswered,
                t.duedate,
                t.closed,
                t.created,
                t.lastupdate,
                (
                    SELECT MIN(te.created)
                    FROM `{p}thread` th
                    JOIN `{p}thread_entry` te
                      ON te.thread_id = th.id
                     AND te.type = 'R'
                     AND te.staff_id > 0
                    WHERE th.object_id = t.ticket_id
                      AND th.object_type = 'T'
                ) AS first_response_at,
                TIMESTAMPDIFF(MINUTE, t.created, (
                    SELECT MIN(te.created)
                    FROM `{p}thread` th
                    JOIN `{p}thread_entry` te
                      ON te.thread_id = th.id
                     AND te.type = 'R'
                     AND te.staff_id > 0
                    WHERE th.object_id = t.ticket_id
                      AND th.object_type = 'T'
                )) AS frt_minutes,
                TIMESTAMPDIFF(MINUTE, t.created, t.closed) AS resolution_minutes
            FROM `{p}ticket` t
            WHERE t.created >= :date_from
              AND t.created <  :date_to
        """
        statuses = f"""
            SELECT id, name, state
            FROM `{p}ticket_status`
        """
        staff = f"""
            SELECT staff_id, CONCAT_WS(' ', firstname, lastname) AS full_name
            FROM `{p}staff`
        """
        departments = f"""
            SELECT id, name
            FROM `{p}department`
        """
        return cls(
            tickets_in_range=tickets_in_range,
            statuses=statuses,
            staff=staff,
            departments=departments,
        )
