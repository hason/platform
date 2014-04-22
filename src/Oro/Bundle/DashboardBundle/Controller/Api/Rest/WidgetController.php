<?php

namespace Oro\Bundle\DashboardBundle\Controller\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\DashboardBundle\Model\WidgetManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Rest\RouteResource("dashboard_widget")
 * @Rest\NamePrefix("oro_api_")
 */
class WidgetController extends FOSRestController implements ClassResourceInterface
{
    /**
     * @param integer $dashboardId
     * @param integer $widgetId
     *
     * @Rest\QueryParam(
     *      name="isExpanded",
     *      requirements="(1)|(0)",
     *      nullable=true,
     *      strict=true,
     *      description="Set collapse or expand"
     * )
     * @Rest\QueryParam(
     *      name="layoutPosition",
     *      nullable=true,
     *      strict=true,
     *      description="Set layout position"
     * )
     *
     * @ApiDoc(
     *      description="Update dashboard widget",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_dashboard_edit",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroDashboardBundle:Dashboard"
     * )
     * @return Response
     */
    public function putAction($dashboardId, $widgetId)
    {
        $dashboard = $this->getDashboard($dashboardId);
        $widget    = $this->getWidget($widgetId);

        if (!$dashboard || !$widget) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        $widget->setExpanded(
            $this->getRequest()->get('isExpanded', $widget->isExpanded())
        );

        $widget->setLayoutPosition(
            $this->getRequest()->get('layoutPosition', $widget->getLayoutPosition())
        );

        $this->getEntityManager()->flush($widget);

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
    }

    /**
     * @param integer $dashboardId
     * @param integer $widgetId
     *
     * @ApiDoc(
     *      description="Delete dashboard widget",
     *      resource=true
     * )
     * @AclAncestor("oro_dashboard_edit")
     * @return Response
     */
    public function deleteAction($dashboardId, $widgetId)
    {
        $dashboard = $this->getDashboard($dashboardId);
        $widget    = $this->getWidget($widgetId);

        if (!$dashboard || !$widget) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        $this->getEntityManager()->remove($widget);
        $this->getEntityManager()->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
    }

    /**
     * @param integer $dashboardId
     *
     * @Rest\Put()
     *
     * @Rest\QueryParam(
     *      name="layoutPositions",
     *      nullable=true,
     *      strict=true,
     *      description="Array of layout positions"
     * )
     *
     * @ApiDoc(
     *      description="Update dashboard widgets positions",
     *      resource=true
     * )
     * @AclAncestor("oro_dashboard_edit")
     * @return Response
     */
    public function putPositionsAction($dashboardId)
    {
        $dashboard = $this->getDashboard($dashboardId);

        if (!$dashboard) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        $layoutPositions = $this->getRequest()->get('layoutPositions', []);
        $widgets         = [];

        foreach ($layoutPositions as $widgetId => $layoutPosition) {
            if ($widget = $this->getWidget($widgetId)) {
                $widget->setLayoutPosition($layoutPosition);

                $widgets[] = $widget;
            }
        }

        $this->getEntityManager()->flush($widgets);

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
    }

    /**
     * @Rest\QueryParam(
     *      name="dashboardId",
     *      nullable=false,
     *      strict=true,
     *      description="Dashboard id"
     * )
     * @Rest\QueryParam(
     *      name="widgetName",
     *      nullable=false,
     *      strict=true,
     *      description="Dashboard widget name"
     * )
     * @ApiDoc(
     *      description="Add widget to dashboard",
     *      resource=true
     * )
     * @AclAncestor("oro_dashboard_edit")
     * @Acl(
     *      id="oro_dashboard_widget_create",
     *      type="create",
     *      permission="CREATE",
     *      class="OroDashboardBundle:DashboardWidget"
     * )
     * @return Response
     */
    public function postAddWidgetAction()
    {
        $dashboardId = $this->getRequest()->get('dashboardId');
        $widgetName = $this->getRequest()->get('widgetName');
        $targetColumn = (int)$this->getRequest()->get('targetColumn', 0);

        if (!$dashboardId || !$widgetName) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        $widgetModel = $this->getWidgetManager()->createWidget($widgetName, $dashboardId, $targetColumn);
        if (!$widgetModel) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        return $this->handleView($this->view($widgetModel, Codes::HTTP_OK));
    }

    /**
     * @param integer $id
     * @return DashboardWidget
     */
    protected function getWidget($id)
    {
        $entity = $this
            ->getEntityManager()
            ->getRepository('OroDashboardBundle:DashboardWidget')
            ->find($id);

        return $entity;
    }

    /**
     * @param integer $id
     * @return Dashboard
     */
    protected function getDashboard($id)
    {
        $entity = $this
            ->getEntityManager()
            ->getRepository('OroDashboardBundle:Dashboard')
            ->find($id);

        return $entity;
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * @return WidgetManager
     */
    protected function getWidgetManager()
    {
        return $this->get('oro_dashboard.widget_manager');
    }
}