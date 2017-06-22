<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * При оплате счета заказ не переводится в стадию выполняется, если таких стадий несколько (для разных типов заказов)
 */
class Version20170622101752 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<EOT
CREATE OR REPLACE FUNCTION set_project_stage_from_payment(p_id character varying)
  RETURNS integer AS
\$BODY\$
DECLARE
    l_invoicsum numeric(10, 2);
    l_paymentsum    numeric(10, 2);
BEGIN
-- miv 29.06.2009: при полной оплате всех счетов, связяных с проектом изменяет его состояние на "4. Выполнение"

    -- 1. получение суммы выставленых счетов по проекту
    -- учитываются счета со статусом  "Выставлен" "Ожидается оплата" "Оплачен" "Оплачен частично"
    select sum(T0.amount) into l_invoicsum from iris_invoice T0
    left join iris_invoicestate T1 on T0.invoicestateid = T1.id
    where T1.code in ('Submited', 'Payment', 'Payed', 'Part')
      and T0.projectid = p_id;

    -- 2. получение суммы произведеных платежей по проекту
    -- учитываются платежи со статусом "Произведен"
    select sum(T0.Amount) into l_paymentsum from iris_payment T0
    left join iris_paymentstate T1 on T0.paymentstateid = T1.id
    where T1.code = 'Completed'
      and T0.projectid = P_id;

    -- 3. если проект оплачен, то изменим его стадию на "Выполнение"
    -- и состояние на "Выполняется"
    if (l_paymentsum >= l_invoicsum) then
        -- изменим стадию и состояние проекта
        --- miv 2017.06.22: добавлен поиск состояния и стадии с учетом projecttypeid
        update iris_project 
        set projectstageid = (select id from iris_projectstage where code='Execution' and projecttypeid = (select projecttypeid from iris_project where id=p_id)),
        projectstateid = (select id from iris_projectstate where code='Execute' and projecttypeid = (select projecttypeid from iris_project where id=p_id))
        where id=p_id;
        return 1;
    end if;
    return 0;
END;
\$BODY\$
  LANGUAGE plpgsql VOLATILE
  COST 100;
EOT;
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sql = <<<EOT
CREATE OR REPLACE FUNCTION set_project_stage_from_payment(p_id character varying)
  RETURNS integer AS
\$BODY\$
DECLARE
    l_invoicsum numeric(10, 2);
    l_paymentsum    numeric(10, 2);
BEGIN
-- miv 29.06.2009: при полной оплате всех счетов, связяных с проектом изменяет его состояние на "4. Выполнение"

    -- 1. получение суммы выставленых счетов по проекту
    -- учитываются счета со статусом  "Выставлен" "Ожидается оплата" "Оплачен" "Оплачен частично"
    select sum(T0.amount) into l_invoicsum from iris_invoice T0
    left join iris_invoicestate T1 on T0.invoicestateid = T1.id
    where T1.code in ('Submited', 'Payment', 'Payed', 'Part')
      and T0.projectid = p_id;

    -- 2. получение суммы произведеных платежей по проекту
    -- учитываются платежи со статусом "Произведен"
    select sum(T0.Amount) into l_paymentsum from iris_payment T0
    left join iris_paymentstate T1 on T0.paymentstateid = T1.id
    where T1.code = 'Completed'
      and T0.projectid = P_id;

    -- 3. если проект оплачен, то изменим его стадию на "Выполнение"
    -- и состояние на "Выполняется"
    if (l_paymentsum >= l_invoicsum) then
        -- изменим стадию и состояние проекта
        update iris_project 
        set projectstageid = (select id from iris_projectstage where code='Execution'),
        projectstateid = (select id from iris_projectstate where code='Execute')
        where id=p_id;
        return 1;
    end if;
    return 0;
END;
\$BODY\$
  LANGUAGE plpgsql VOLATILE
  COST 100;
EOT;
        $this->addSql($sql);
    }
}
